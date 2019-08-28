<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'signature_key' => 'Engine0Milk12Next',
    'gateway_url' => 'https://gateway.yorkshirepayments.com/direct/',
    'merchant_id' => '101381', // 3d secure
    // 'merchant_id' => '101380', // non 3d secure
    '3DSecure' => true,
    'country_code' => '826',
    'currency_code' => '826'
];
