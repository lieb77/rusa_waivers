<?php
/**
 * @file RusaWaiversController.php
 *
 * Author: Paul Lieberman
 * Created: 6/12/2020
 *
 * RUSA Perm Registration - SmartWaiver integration
 *
 */


namespace Drupal\rusa_waivers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rusa_api\RusaPermanents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\user\Entity\User;

/**
 * Class RusaWaiversController.
 */
class RusaWaiversController extends ControllerBase {

    /**
     * Drupal\smartwaiver\ClientInterface definition.
     *
     * @var \Drupal\smartwaiver\ClientInterface
     */
    protected $smartwaiverClient;
 	protected $entityTypeManager;
	protected $currentUser;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        $instance = parent::create($container);
        $instance->smartwaiverClient = $container->get('smartwaiver.client');
		$instance->entityTypeManager = $container->get('entity_type.manager');
		$instance->currentUser       = $container->get('current_user');
        return $instance;
    }

    /**
     * Waivers.
     *
     * Display a list of all waivers
     *
     */
    public function waivers() {
        $waivers = $this->smartwaiverClient->waivers([]);

        foreach ($waivers['waivers'] as $waiver) {
            $wids[] = $waiver['waiverId'];
        }

        // oop thought waiver ids and load each waiver
        foreach ($wids as $wid) {
            $waiver = $this->smartwaiverClient->waiver($wid);
            //dpm($waiver);
            $fields = $waiver->customWaiverFields;
            $cfields = [];
            foreach ($fields as $field) {
                $cfields[$field['displayText']] = $field['value'];
            }

            // Get the signature which is a base64 encoded PNG
            $img = $this->smartwaiverClient->get_signature($wid)->participantSignatures[0];

            // Build the table rows
            $rows[] = [
                $waiver->createdOn,
                $waiver->firstName,
                $waiver->lastName,
                $waiver->email,
                $waiver->tags[0],
                $cfields['Perm #'],
                $cfields['Date you want to ride'],
                $this->t("<img src='" . $img . "' alt='signature' style='max-height: 75px;' />"),
            ];
        }

        $header = ['Created on', 'First name', 'Last name', 'Email', 'RUSA #', 'Perm #','Ride date', 'Signature'];
        $output = [
            '#theme'    => 'table',
            '#header'   => $header,
            '#rows'     => $rows,
        ];

        return($output);

    }


    /**
     * Incoming waiver
     *
     * Rider has been redirected here after signing a waiver
     * The waiver ID is in the query string
     * We want to retrieve the waiver and then redirect rider to their profile permanents tab
     *
     */
    public function incoming() {

        $request   = \Drupal::request();
        $query     = $request->query; 
    	$wid       = $query->get('waiverid');

		// The waiver may not be ready yet
	    sleep(5);	
       	$waiver = $this->smartwaiverClient->waiver($wid);
       	
		// Now we have the waiver
        $tags      = $waiver->tags[0];
        $fields    = $waiver->customWaiverFields;
        $pfields   = $waiver->participants[0]['customParticipantFields'];

        [$mid, $pid] = explode(' ', $tags);


        // Get the mid entered in participant info
        foreach ($pfields as $field) {
            $wmid = $field['value'];
        }

        // Get the custom field data
        foreach ($fields as $field) {
            $cfields[$field['displayText']] = $field['value'];
        }
        $wpid = $cfields['Perm #'];
     
		// Make sure mid matches what we passed in the tags
		if ($mid != $wmid) {
            $this->messenger()->addWarning($this->t('RUSA # entered in waiver is not the same as the rider who submitted the form.'));
            return $this->redirect('rusa_perm.reg',['user' => $this->currentUser->id()]);
		}

        // Make sure pid matches what we passed in the tags
		if ($pid != $wpid) {
            $this->messenger()->addWarning($this->t('Route # entered in waiver is not the same as what was entered in the form.'));
            return $this->redirect('rusa_perm.reg',['user' => $this->currentUser->id()]);
		}


        // Convert the date
        $date = strtotime($cfields['Date you want to ride']);
        $date = date("Y-m-d", $date);

		// Save the registration
 		$reg = \Drupal::entityTypeManager()->getStorage('rusa_perm_reg_ride')->create(
            [
                'field_date_of_ride'    => $date,
                'field_perm_number'     => $pid,
                'field_waiver_id'       => $wid, 
                'field_rusa_member_id'  => $mid,
            ]);
        $reg->save();

		// Return to user profile Permanents tab
        return $this->redirect('rusa_perm.reg',['user' => $this->currentUser->id()]);
    }

}// End of Class
