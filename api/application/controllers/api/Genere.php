<?php

class Genere extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('genere/genere_model');
        $recordCount = $this->genere_model->get_genere_count();

        if ($id == NULL) {
            //give multiple records...
            $pageSize = $this->input->get('pagesize', true);
            $page = $this->input->get('page', true);
            $sortBy = $this->input->get('sortby', true);
            $sortOrder = $this->input->get('sortorder', true);
            $objFilter = $this->input->get('filter', true);
            $multipleIds = $this->input->get('mid', true);

            $arr = (array) $objFilter;
            if (!$arr)
                $objFilter = NULL;

            if (empty($pageSize))
                $pageSize = 10;

            if (empty($page))
                $page = 1;

            if (empty($sortBy))
                $sortBy = "id";

            if (empty($sortOrder))
                $sortOrder = 'desc';

            if (empty($objFilter))
                $objFilter = NULL;
            else
                $objFilter = json_decode($objFilter);

            if (empty($multipleIds))
                $multipleIds = "";
            else
                $multipleIds = trim($multipleIds);
            if (CUtility::endsWith($multipleIds, ",")) {
                $multipleIds = substr($multipleIds, 0, strlen($multipleIds) - 1);
            }

            $filterString = "";
            if ($objFilter != NULL) {
                foreach ($objFilter as $key => $value) {
                    if (
                            CUtility::endsWith($key, "=") ||
                            CUtility::endsWith($key, "!=") ||
                            CUtility::endsWith($key, ">") ||
                            CUtility::endsWith($key, ">=") ||
                            CUtility::endsWith($key, "<") ||
                            CUtility::endsWith($key, "<=")
                    )
                        $filterString .= $key . $value . " and ";
                    else
                        $filterString .= $key . " like('%" . $value . "%') and ";
                }
                $filterString = substr($filterString, 0, strlen($filterString) - 5);
            }

            if (strlen($multipleIds) > 0) {
                if (strlen($filterString) == 0)
                    $filterString = "id in (" . $multipleIds . ")";
                else
                    $filterString .= " and id in (" . $multipleIds . ")";
            }

            $totalNoOfPages = intdiv($recordCount, $pageSize);
            $remainder = $recordCount % $pageSize;
            if ($remainder > 0)
                $totalNoOfPages++;

            $offset = ($page - 1) * $pageSize;
            $generes = $this->genere_model->get_paginated_genere($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($generes) > 0) {
                $response = $this->get_success_response($generes, 'Genere page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($generes);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($generes, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objGenere = $this->genere_model->get_genere($id);
            if ($objGenere == NULL) {
                $response = $this->get_failed_response(NULL, "Genere not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objGenere, "Genere details..!");
                $this->set_output($response);
            }
        }
    }

    function index_post() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        $objGenere = new Genere_object();
        $objGenere->id = 0;
        $objGenere->class_group_id = $request['class_group_id'];
        $objGenere->class_name = $request['class_name'];
        $objGenere->numeric_equivalent = $request['numeric_equivalent'];

        $this->load->model('genere/genere_model');
        $objGenere = $this->genere_model->add_genere($objGenere);
        if ($objGenere === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating genere...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objGenere, "Genere created successfully...!");
            $this->set_output($response);
        }
    }

    function index_put($id) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        $this->load->model('genere/genere_model');
        $objGenereOriginal = $this->genere_model->get_genere($id);

        $objGenere = new Genere_object();
        $objGenere->id = $objGenereOriginal->id;
        $objGenere->class_group_id = $request['class_group_id'];
        $objGenere->class_name = $request['class_name'];
        $objGenere->numeric_equivalent = $request['numeric_equivalent'];
        $objGenere = $this->genere_model->update_genere($objGenere);
        if ($objGenere === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating genere...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objGenere, "Genere updated successfully...!");
            $this->set_output($response);
        }
    }

    function index_delete($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($id != NULL) {
            $this->load->model('genere/genere_model');
            $deleted = $this->genere_model->delete_genere($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "Genere deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "Genere deletion failed...!");
                $this->set_output($response);
            }
        }
    }

    public function get_all_classes_for_employee_get() {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $objUser = $this->get_logged_user();
        if ($objUser == NULL) {
            $response = $this->get_failed_response(NULL, "Data not available");
            $this->set_output($response);
        } else {
            if ($objUser->role_id == 4 || $objUser->role_id == 3) {
                $classes = array();
                $employeeId = $objUser->employee->id;
                $academicSession = CPreference::$academicSession;
                $pdo = CDatabase::getPdo();
                $sql = "select DISTINCT genere.* from content left join genere on content.class_id = genere.id where content.uploaded_by = ? and academic_session = ? order by genere.numeric_equivalent";
                $statement = $pdo->prepare($sql);
                $statement->execute(array($employeeId, $academicSession));
                while ($row = $statement->fetch()) {
                    $objGenere = new Genere_object();
                    $objGenere->id = $row['id'];
                    $objGenere->class_group_id = $row['class_group_id'];
                    $objGenere->class_name = $row['class_name'];
                    $objGenere->numeric_equivalent = $row['numeric_equivalent'];
                    $classes[] = $objGenere;
                }
                $statement = NULL;
                $pdo = NULL;

                if (count($classes) > 0) {
                    $response = $this->get_success_response($classes, "List of classes");
                    $this->set_output($response);
                } else {
                    $response = $this->get_failed_response(NULL, "Data not available");
                    $this->set_output($response);
                }
            } else {
                $response = $this->get_failed_response(NULL, "Data not available");
                $this->set_output($response);
            }
        }
    }

    function test_get() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $employeeId = 2;           
        $academicSession = CPreference::$academicSession;
        $pdo = CDatabase::getPdo();
        $sql = "select DISTINCT genere.* from content left join genere on content.class_id = genere.id where content.uploaded_by = ? and academic_session = ? order by genere.numeric_equivalent";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($employeeId, $academicSession));
        while ($row = $statement->fetch()) {
            $objGenere = new Genere_object();
            $objGenere->id = $row['id'];
            $objGenere->class_group_id = $row['class_group_id'];
            $objGenere->class_name = $row['class_name'];
            $objGenere->numeric_equivalent = $row['numeric_equivalent'];
            $classes[] = $objGenere;
        }
        $statement = NULL;
        $pdo = NULL;

        if (count($classes) > 0) {
            $response = $this->get_success_response($classes, "List of classes");
            $this->set_output($response);
        } else {
            $response = $this->get_failed_response(NULL, "Data not available");
            $this->set_output($response);
        }
    }

}
