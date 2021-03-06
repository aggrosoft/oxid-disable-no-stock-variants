<?php

$sMetadataVersion = '2.0';

$aModule = array(
    'id'           => 'agdisablenostockvariants',
    'title'        => 'Aggrosoft Disable no stock variants',
    'description'  => [
        'de' => 'Disable not on stock multidimensional variants',
        'en' => 'Disable not on stock multidimensional variants'
    ],
    'thumbnail'    => '',
    'version'      => '1.0.4',
    'author'       => 'Aggrosoft GmbH',
    'extend'      => [
        \OxidEsales\Eshop\Application\Model\VariantHandler::class => \Aggrosoft\DisableNoStockVariants\Application\Model\VariantHandler::class
    ]
);
