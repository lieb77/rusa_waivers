<?php

namespace Drupal\rusa_waivers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        $instance = parent::create($container);
        $instance->smartwaiverClient = $container->get('smartwaiver.client');
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

}
