<?php

class Chapter extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('chapter/chapter_model');
        $recordCount = $this->chapter_model->get_chapter_count();

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
            $chapters = $this->chapter_model->get_paginated_chapter($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($chapters) > 0) {
                $response = $this->get_success_response($chapters, 'Chapter page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($chapters);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($chapters, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objChapter = $this->chapter_model->get_chapter($id);
            if ($objChapter == NULL) {
                $response = $this->get_failed_response(NULL, "Chapter not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objChapter, "Chapter details..!");
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

        $objChapter = new Chapter_object();
        $objChapter->id = 0;
        $objChapter->subject_id = $request['subject_id'];
        $objChapter->chapter_name = $request['chapter_name'];


        $this->load->model('chapter/chapter_model');
        $objChapter = $this->chapter_model->add_chapter($objChapter);
        if ($objChapter === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating genechapterre...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objChapter, "Chapter created successfully...!");
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

        $this->load->model('chapter/chapter_model');
        $objChapterOriginal = $this->chapter_model->get_chapter($id);

        $objChapter = new Chapter_object();
        $objChapter->id = $objChapterOriginal->id;
        $objChapter->subject_id = $request['subject_id'];
        $objChapter->chapter_name = $request['chapter_name'];
        $objChapter = $this->chapter_model->update_chapter($objChapter);
        if ($objChapter === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating chapter...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objChapter, "Chapter updated successfully...!");
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
            $this->load->model('chapter/chapter_model');
            $deleted = $this->chapter_model->delete_chapter($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "Chapter deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "Chapter deletion failed...!");
                $this->set_output($response);
            }
        }
    }

}
