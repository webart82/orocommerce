<?php

namespace Oro\Bundle\DPDBundle\Controller;

use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\Repository\ShippingServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/dpd")
 */
class DPDController extends Controller
{
    const CSV_DELIMITER = ',';

    /**
     * @Route("/rates/download/{id}", name="oro_dpd_rates_download", requirements={"id"="\d+"})
     * @AclAncestor("oro_integration_view")
     *
     * @param DPDTransport $transport
     *
     * @return Response
     */
    public function ratesDownloadAction(DPDTransport $transport)
    {
        /** @var ShippingServiceRepository $repository */
        $repository = $this->container
            ->get('doctrine')
            ->getManagerForClass('OroDPDBundle:ShippingService')
            ->getRepository('OroDPDBundle:ShippingService');
        $shippingServiceCodes = $repository->getAllShippingServiceCodes();

        $response = new StreamedResponse();
        $response->setCallback(function () use ($transport, $shippingServiceCodes) {
            $handle = fopen('php://output', 'rb+');

            // Add BOM to fix UTF-8 in Excel
            fwrite($handle, $bom = (chr(0xEF).chr(0xBB).chr(0xBF)));

            // Add the header of the CSV file
            $header = [
                'Shipping Service Code ('.implode('/', $shippingServiceCodes).')',
                'Country Code (ISO 3166-1 alpha-2)',
                'Region Code (ISO 3166-2)',
                'Weight Value ('.$transport->getUnitOfWeight().')',
                'Price Value',
            ];
            fputcsv($handle, $header, self::CSV_DELIMITER);

            foreach ($transport->getRates() as $rate) {
                $row = [
                    $rate->getShippingService() ? $rate->getShippingService()->getCode() : null,
                    $rate->getCountry() ? $rate->getCountry()->getIso2Code() : null,
                    $rate->getRegion() ? $rate->getRegion()->getCombinedCode() : null,
                    $rate->getWeightValue(),
                    $rate->getPriceValue(),
                ];
                fputcsv($handle, $row, self::CSV_DELIMITER);
            }

            fclose($handle);
        });

        $exportFileName = 'dpd_rates_'.date('Ymd_His').'.csv';

        $response->setStatusCode(200);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $exportFileName
        );
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
