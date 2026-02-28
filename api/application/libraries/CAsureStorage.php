<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CAFS
 *
 * @author Jawahar
 */
//require_once(realpath($_SERVER["DOCUMENT_ROOT"]) . '/olc/services/vendor/autoload.php');
require_once('vendor/autoload.php'); 

use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

class CAsureStorage {
    ## adds file to the storage. Usage: storageAddFile("myContainer", "C:\path\to\file.png", "filename-on-storage.png")

    public function storageAddFile($containerName, $file, $fileName) {
        # Setup a specific instance of an Azure::Storage::Client
        //$connectionString = "DefaultEndpointsProtocol=https;AccountName=" . getenv('STORAGE_ACCOUNT_NAME') . ";AccountKey=" . getenv('STORAGE_ACCOUNT_KEY');
        //$connectionString = "DefaultEndpointsProtocol=https;AccountName=onlineclasses;AccountKey=p2g3F5jfFnqiR/UCRwvlqQS+88XGDLfevy88fc390CCxj1R24mSm4G/DFOpkX/9G6oLjqGVmh7SMLvTzCJRkjw==;EndpointSuffix=core.windows.net";
        $connectionString = "DefaultEndpointsProtocol=https;AccountName=stephensonlineclasses;AccountKey=1cM1ynWJjqG34y4YdLQ83wVipVPc8aAtB/kNFpZ5OnAWh1toARkNDIOE0lyrzOP6gM4CrAlE6bR8wR+s2kBDAQ==;EndpointSuffix=core.windows.net";
        // Create blob client.
        $blobClient = BlobRestProxy::createBlobService($connectionString);

        $handle = @fopen($file, "r");
        if ($handle) {
            $options = new CreateBlockBlobOptions();
            $mime = NULL;

            try {
                // identify mime type
                //$mimes = new \Mimey\MimeTypes;
                //$mime = $mimes->getMimeType(pathinfo($fileName, PATHINFO_EXTENSION));
                
                $mime = $this->detectMimeTypeByFileExtension(PATHINFO_EXTENSION);
                
                // set content type
                $options->setContentType($mime);
            } catch (Exception $e) {
                echo "Failed to read mime from '" . $file . ": " . $e;
                error_log("Failed to read mime from '" . $file . ": " . $e);
            }

            try {
                if ($mime) {
                    $cacheTime = $this->getCacheTimeByMimeType($mime);
                    if ($cacheTime) {
                        $options->setCacheControl("public, max-age=" . $cacheTime);
                    }
                }
                $blobClient->createBlockBlob($containerName, $fileName, $handle, $options);                
            } catch (Exception $e) {
                echo "Failed to upload file '" . $file . "' to storage: " . $e;
                error_log("Failed to upload file '" . $file . "' to storage: " . $e);
            }

            @fclose($handle);
            return true;
        } else {
            echo "Failed to open file '" . $file . "' to upload to storage.";
            error_log("Failed to open file '" . $file . "' to upload to storage.");
            return false;
        }
    }

    ## get cache time by mime type

    public function getCacheTimeByMimeType($mime) {
        $mime = strtolower($mime);

        $types = array(
            "application/json" => 604800, // 7 days
            "application/javascript" => 604800, // 7 days
            "application/xml" => 604800, // 7 days
            "application/xhtml+xml" => 604800, // 7 days
            "image/bmp" => 604800, // 7 days
            "image/gif" => 604800, // 7 days
            "image/jpeg" => 604800, // 7 days
            "image/png" => 604800, // 7 days
            "image/tiff" => 604800, // 7 days
            "image/svg+xml" => 604800, // 7 days
            "image/x-icon" => 604800, // 7 days
            "text/plain" => 604800, // 7 days
            "text/html" => 604800, // 7 days
            "text/css" => 604800, // 7 days
            "text/richtext" => 604800, // 7 days
            "text/xml" => 604800, // 7 days
        );

        // return value
        if (array_key_exists($mime, $types)) {
            return $types[$mime];
        }

        return FALSE;
    }

    ## removes file from the storage. Usage: storageAddFile("myContainer", "filename-on-storage.png")

    public function storageRemoveFile($containerName, $fileName) {
        # Setup a specific instance of an Azure::Storage::Client
        $connectionString = "DefaultEndpointsProtocol=https;AccountName=" . getenv('STORAGE_ACCOUNT_NAME') . ";AccountKey=" . getenv('STORAGE_ACCOUNT_KEY');
        // Create blob client.
        $blobClient = BlobRestProxy::createBlobService($connectionString);

        try {
            $blobClient->deleteBlob($containerName, $fileName);
        } catch (Exception $e) {
            error_log("Failed to delete file '" . $fileName . "' from storage");
        }

        return true;
    }

    function detectMimeTypeByFileExtension($extension) {
        $extensionToMimeTypeMap = $this->getExtensionToMimeTypeMap();

        if (isset($extensionToMimeTypeMap[$extension])) {
            return $extensionToMimeTypeMap[$extension];
        }
        return 'text/plain';
    }

    public function getExtensionToMimeTypeMap() {
        return [
            'hqx' => 'application/mac-binhex40',
            'cpt' => 'application/mac-compactpro',
            'csv' => 'text/x-comma-separated-values',
            'bin' => 'application/octet-stream',
            'dms' => 'application/octet-stream',
            'lha' => 'application/octet-stream',
            'lzh' => 'application/octet-stream',
            'exe' => 'application/octet-stream',
            'class' => 'application/octet-stream',
            'psd' => 'application/x-photoshop',
            'so' => 'application/octet-stream',
            'sea' => 'application/octet-stream',
            'dll' => 'application/octet-stream',
            'oda' => 'application/oda',
            'pdf' => 'application/pdf',
            'ai' => 'application/pdf',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            'smi' => 'application/smil',
            'smil' => 'application/smil',
            'mif' => 'application/vnd.mif',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'wbxml' => 'application/wbxml',
            'wmlc' => 'application/wmlc',
            'dcr' => 'application/x-director',
            'dir' => 'application/x-director',
            'dxr' => 'application/x-director',
            'dvi' => 'application/x-dvi',
            'gtar' => 'application/x-gtar',
            'gz' => 'application/x-gzip',
            'gzip' => 'application/x-gzip',
            'php' => 'application/x-httpd-php',
            'php4' => 'application/x-httpd-php',
            'php3' => 'application/x-httpd-php',
            'phtml' => 'application/x-httpd-php',
            'phps' => 'application/x-httpd-php-source',
            'js' => 'application/javascript',
            'swf' => 'application/x-shockwave-flash',
            'sit' => 'application/x-stuffit',
            'tar' => 'application/x-tar',
            'tgz' => 'application/x-tar',
            'z' => 'application/x-compress',
            'xhtml' => 'application/xhtml+xml',
            'xht' => 'application/xhtml+xml',
            'zip' => 'application/x-zip',
            'rar' => 'application/x-rar',
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mpga' => 'audio/mpeg',
            'mp2' => 'audio/mpeg',
            'mp3' => 'audio/mpeg',
            'aif' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'ram' => 'audio/x-pn-realaudio',
            'rm' => 'audio/x-pn-realaudio',
            'rpm' => 'audio/x-pn-realaudio-plugin',
            'ra' => 'audio/x-realaudio',
            'rv' => 'video/vnd.rn-realvideo',
            'wav' => 'audio/x-wav',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpe' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'css' => 'text/css',
            'html' => 'text/html',
            'htm' => 'text/html',
            'shtml' => 'text/html',
            'txt' => 'text/plain',
            'text' => 'text/plain',
            'log' => 'text/plain',
            'rtx' => 'text/richtext',
            'rtf' => 'text/rtf',
            'xml' => 'application/xml',
            'xsl' => 'application/xml',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpe' => 'video/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'movie' => 'video/x-sgi-movie',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dot' => 'application/msword',
            'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'word' => 'application/msword',
            'xl' => 'application/excel',
            'eml' => 'message/rfc822',
            'json' => 'application/json',
            'pem' => 'application/x-x509-user-cert',
            'p10' => 'application/x-pkcs10',
            'p12' => 'application/x-pkcs12',
            'p7a' => 'application/x-pkcs7-signature',
            'p7c' => 'application/pkcs7-mime',
            'p7m' => 'application/pkcs7-mime',
            'p7r' => 'application/x-pkcs7-certreqresp',
            'p7s' => 'application/pkcs7-signature',
            'crt' => 'application/x-x509-ca-cert',
            'crl' => 'application/pkix-crl',
            'der' => 'application/x-x509-ca-cert',
            'kdb' => 'application/octet-stream',
            'pgp' => 'application/pgp',
            'gpg' => 'application/gpg-keys',
            'sst' => 'application/octet-stream',
            'csr' => 'application/octet-stream',
            'rsa' => 'application/x-pkcs7',
            'cer' => 'application/pkix-cert',
            '3g2' => 'video/3gpp2',
            '3gp' => 'video/3gp',
            'mp4' => 'video/mp4',
            'm4a' => 'audio/x-m4a',
            'f4v' => 'video/mp4',
            'webm' => 'video/webm',
            'aac' => 'audio/x-acc',
            'm4u' => 'application/vnd.mpegurl',
            'm3u' => 'text/plain',
            'xspf' => 'application/xspf+xml',
            'vlc' => 'application/videolan',
            'wmv' => 'video/x-ms-wmv',
            'au' => 'audio/x-au',
            'ac3' => 'audio/ac3',
            'flac' => 'audio/x-flac',
            'ogg' => 'audio/ogg',
            'kmz' => 'application/vnd.google-earth.kmz',
            'kml' => 'application/vnd.google-earth.kml+xml',
            'ics' => 'text/calendar',
            'zsh' => 'text/x-scriptzsh',
            '7zip' => 'application/x-7z-compressed',
            'cdr' => 'application/cdr',
            'wma' => 'audio/x-ms-wma',
            'jar' => 'application/java-archive',
        ];
    }

}
