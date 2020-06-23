<?php

namespace Drupal\rusa_waivers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rusa_api\RusaPermanents;
use Drupal\Core\Entity\EntityTypeManagerInterface;


/**
 * Class RusaIncomingController.
 */
class RusaIncomingController extends ControllerBase {

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
        $mid       = $waiver->tags[0];
        $fields    = $waiver->customWaiverFields;

        foreach ($fields as $field) {
            $cfields[$field['displayText']] = $field['value'];
        }
        $pid =  $cfields['Perm #'];

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


        // Get the signature which is a base64 encoded PNG
        $img = $this->smartwaiverClient->get_signature($wid)->participantSignatures[0];


        // Build the table rows
        $rows[] = [
            $waiver->createdOn,
            $waiver->firstName,
            $waiver->lastName,
            $waiver->email,
            $waiver->tags[0],
            $pid,
            $date,
            $this->t("<img src='" . $img . "' alt='signature' style='max-height: 75px;' />"),
        ];


        $header = ['Created on', 'First name', 'Last name', 'Email', 'RUSA #', 'Perm #','Ride date', 'Signature'];
        $output['waiver'] = [
            '#theme'    => 'table',
            '#header'   => $header,
            '#rows'     => $rows,
        ];


		$output['section'] = ['#markup' => $this->t('<h3>Perm Info</h3>')];
		$perm = $this->getPerm($cfields['Perm #']);
 		$output['perm'] = [
			'#theme'    => 'table',
			'#header'   => ['Name', 'Km', 'Feet', 'Description'],
			'#rows'     => [[$perm->name, $perm->dist, $perm->climbing, $perm->description]],
			'#attributes' => ['class' => ['rusa-table']],
		];


        return($output);
    }


	private function getPerm($pid) {
        $permobj = new RusaPermanents(['key' => 'pid', 'val' => $pid]);
        $perm = $permobj->getPermanent($pid);
		return($perm);
	}

}
