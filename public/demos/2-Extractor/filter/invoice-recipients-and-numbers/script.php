<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = glob($assetsDirectory . '/pdfs/tektown/invoices/[0-9]*.pdf');

// prepare the resulting array
$invoicesByCustomerName = [];

foreach ($files AS $file) {
    // initiate a document instance
    $document = \SetaPDF_Core_Document::loadByFilename($file);

    // initiate an extractor instance
    $extractor = new SetaPDF_Extractor($document);

    // get the plain strategy shich is the default strategy
    $strategy = $extractor->getStrategy();

    // define a rectangle filter for the invoice recipient name
    $recipientNameFilter = new SetaPDF_Extractor_Filter_Rectangle(
        new \SetaPDF_Core_Geometry_Rectangle(40, 665, 260, 700),
        SetaPDF_Extractor_Filter_Rectangle::MODE_CONTACT,
        'recipient'
    );

    // define another rectangle filter for the invoice number
    $invoiceNofilter = new SetaPDF_Extractor_Filter_Rectangle(
        new \SetaPDF_Core_Geometry_Rectangle(512, 520, 580, 540),
        SetaPDF_Extractor_Filter_Rectangle::MODE_CONTACT,
        'invoiceNo'
    );

    // pass the filters to the strategy by using a filter chain
    $strategy->setFilter(new SetaPDF_Extractor_Filter_Multi([$recipientNameFilter, $invoiceNofilter]));

    // now walk through the pages and ...
    $pages = $document->getCatalog()->getPages();
    for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {

        // extract the content found by the specific filters.
        $result = $extractor->getResultByPageNumber($pageNo);

        $invoiceNo = $result['invoiceNo'];
        $recipient = $result['recipient'];

        // create single lines of the recipient
        $recipient = explode("\n", $recipient);

        // the name can be found in the first item
        $name = array_shift($recipient);
        // the optinal company name is left over
        $companyName = array_shift($recipient);

        // create a unique key
        $key = $name . '|' . $companyName;

        // save the name and company data and prepare the reuslt
        if (!isset($invoicesByCustomerName[$key])) {
            $invoicesByCustomerName[$key] = [
                'name'        => $name,
                'companyName' => $companyName,
                'invoices'    => []
            ];
        }

        // add the invoice and page number to the result
        $invoicesByCustomerName[$key]['invoices'][] = [
            'invoiceNo' => $invoiceNo,
            'pageNo'    => $pageNo,
            'file'  => $file
        ];
    }

    // release memory
    $extractor->cleanUp();
    $document->cleanUp();
}

// output the resolved data:
foreach ($invoicesByCustomerName AS $customerData) {
    echo '<h1>Customer: ' . htmlentities($customerData['name']) . ' / '
        . htmlentities($customerData['companyName']) . '</h1>';

    echo '<ul>';
    foreach($customerData['invoices'] AS $invoice) {
        echo '<li>Invoice Number #' . htmlentities($invoice['invoiceNo'])
            . ' on page #' . $invoice['pageNo'] . ' in ';
        echo htmlspecialchars(substr($invoice['file'], strlen($assetsDirectory . '/pdfs/')));
        echo '</li>';
    }

    echo '</ul>';
}
