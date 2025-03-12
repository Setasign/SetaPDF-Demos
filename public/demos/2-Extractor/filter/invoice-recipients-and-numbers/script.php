<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Geometry\Rectangle;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Filter\Multi as MultiFilter;
use setasign\SetaPDF2\Extractor\Filter\Rectangle as RectangleFilter;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = glob($assetsDirectory . '/pdfs/tektown/invoices/[0-9]*.pdf');

// prepare the resulting array
$invoicesByCustomerName = [];

foreach ($files AS $file) {
    // initiate a document instance
    $document = Document::loadByFilename($file);

    // initiate an extractor instance
    $extractor = new Extractor($document);

    // get the plain strategy which is the default strategy
    $strategy = $extractor->getStrategy();

    // define a rectangle filter for the invoice recipient name
    $recipientNameFilter = new RectangleFilter(
        new Rectangle(40, 665, 260, 700),
        RectangleFilter::MODE_CONTACT,
        'recipient'
    );

    // define another rectangle filter for the invoice number
    $invoiceNofilter = new RectangleFilter(
        new Rectangle(512, 520, 580, 540),
        RectangleFilter::MODE_CONTACT,
        'invoiceNo'
    );

    // pass the filters to the strategy by using a filter chain
    $strategy->setFilter(new MultiFilter([$recipientNameFilter, $invoiceNofilter]));

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
        // the optional company name is left over
        $companyName = array_shift($recipient);

        // create a unique key
        $key = $name . '|' . $companyName;

        // save the name and company data and prepare the result
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
