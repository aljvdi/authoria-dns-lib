<?php
/**
 * Authoria DNS Library Demo
 * This file demonstrates the usage of the AuthoriaDNS library
 * @package AuthoriaDNS
 * @author Alex Javadi
 * @version 1.0.0
 * @since 1.0.0
 */

// Include the Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Use the AuthoriaDNS namespace
use Javadi\Authoria\Dns\AuthoriaDNS;

// Create a new AuthoriaDNS object
$authoria = new AuthoriaDNS("YOUR_INSTANCE_URL");

// New Verification Request
print_r($authoria->new("example.com"));
// Response:
/*
    Array
    (
        [id] => UUID
        [token] => authoria-dns-verification=HASH_TOKEN
        [how_to_verify] => Add a TXT record with the value 'authoria-dns-verification=HASH_TOKEN' to your domain's DNS records
    )
*/

// Get Verification Request Status
print_r($authoria->verify("YOUR-VERIFICATION-ID"));
// Response:
/*
    Array
    (
        [id] => UUID
        [domain] => example.com
        [verified] => true || false (boolean)
        [status] => PENDING || VERIFIED || EXPIRED || NOT_FOUND (string)
    )
*/


// Bulk Verification Request Status
print_r($authoria->bulkVerify(["YOUR-VERIFICATION-ID-1", "YOUR-VERIFICATION-ID-2"]));
// Response:
/*
    Array
    (
        Array
            (
                [id] => UUID
                [domain] => example.com
                [verified] => true || false (boolean)
                [status] => PENDING || VERIFIED || EXPIRED || NOT_FOUND (string)
            )
        ,
        Array
            (
                [id] => UUID
                [domain] => example.com
                [verified] => true || false (boolean)
                [status] => PENDING || VERIFIED || EXPIRED || NOT_FOUND (string)
            )
    )
*/
