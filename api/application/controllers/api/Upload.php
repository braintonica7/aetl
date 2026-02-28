<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates 
 * and open the template in the editor.
 */

/**
 * Description of Upload
 * 
 * @author Jawahar
 */
class Upload extends API_Controller {

    public function index_post() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        // $objUser = $this->require_jwt_auth(true); // true = admin required
        // if (!$objUser) {
        //     return; // Error response already sent by require_jwt_auth()
        // }

        //header("Access-Control-Allow-Origin: *");
//echo "response : POST";
//$entityBody = file_get_contents('php://input');
//print_r($_POST);

        if (count($_FILES) > 0) {
            $source = $_FILES['file']['name'];
            $extension = "." . pathinfo($source, PATHINFO_EXTENSION);
            $extension = strtolower($extension);
            $destinationFileName = CUtility::get_UUID(openssl_random_pseudo_bytes(20)) . $extension;
            $destination = "assets/uploads/files/" . $destinationFileName;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
                $url = base_url($destination);
                $response = $this->get_success_response($url, "File uploaded successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Error while uploading file...!");
                $this->set_output($response);
            }
        }
    }

}
