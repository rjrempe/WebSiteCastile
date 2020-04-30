<?php
    include_once 'database/db.php';
    include_once 'inquiry/inquiry.php';
    include_once 'restService.php';

    // required headers
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    /* 
    */

    $reqMethod = $_SERVER['REQUEST_METHOD'];
    
    // echo "REQUEST is $reqMethod";

    $ic = new InquiryController();
    
    $ic->processRequest();

    class InquiryController{

        private $database = null;
  
        // initialize object
        private $inq = null;

        private $validInquiry = true;


    // constructor with $db as database connection
        public function __construct(){

        }
        public function processRequest(){
        
            $this->database = new Db('realty');
  
            // initialize object
            $this->inq = new Inquiry($this->database);

            $data = file_get_contents("php://input");

            if($data === FALSE){
                $this->validInquiry = false;
            }
            else{
                $inqData = json_decode($data);

                if (isset($inqData->name)) {
                    $this->inq->name = $inqData->name;
                }
                if (isset($inqData->phone)) {
                    $this->inq->phone = $inqData->phone;
                }
                if (isset($inqData->request)) {
                    $this->inq->request = $inqData->request;
                }

                if(empty($inqData->name) && empty($inqData->phone) && empty($inqData->request)){

                    $this->validInquiry = false;
                }
            }


            $requestUri = $_SERVER['REQUEST_URI'];
            $id = 0;
            $q = "";

            $reqMethod = $_SERVER['REQUEST_METHOD'];

            if(isset($_GET["id"])){
              $id =  $_GET['id'];
            }
            if(isset($_GET["q"])){
              $q =  $_GET['q'];
            }
            
            // echo "  Q is $q  ";

            switch ($reqMethod) {
                case 'GET':
                    // read
                    //echo 'Perform read()';
                    $this->read($id);
                    break;

                case 'POST':
                    //echo 'Perform create()';
                    $this->create();
                    break;

                case 'PUT':
                    //echo 'Perform update()';
                    $this->update($id);
                    break;
                
                case 'DELETE':
                    $this->delete($id);
                    break;

                default:
                    break;
            }
        
        }

        //
        //  create()    REST POST operation
        //
        function create(){

            $createResult = new restResponse();

            // get posted data

            //  php://input:            This is a read-only stream that allows us to read raw data from the request body. 
            //                          It returns all the raw data after the HTTP-headers of the request, regardless of the content type.
            //
            //  file_get_contents()     This function in PHP is used to read a file into a string.
            //
            //  json_decode()           This function takes a JSON string and converts it into a PHP variable that may be an array or an object.
            //
            // make sure data is not empty

            if(! $this->validInquiry){
                // set response code - 400 bad request
                http_response_code(400);
  
                $createResult->status = "error";
                $createResult->message = "Required Inquiry data not provided.";

                array_push($createResult->data, $this->inq);
            }
            else{
                  // attempt to create the Inquiry
            
                if($this->inq->insert()){
  
                    // set response code - 201 created
                    http_response_code(201);
  
                    $createResult->status = "success";
                    $createResult->message = "Inquiry was created.";
                    $createResult->count = 1;

                    array_push($createResult->data, $this->inq);
                }
                else{
  
                    // set response code - 503 service unavailable
                    http_response_code(503);

                    $createResult->status = "error";
                    $createResult->message = "Unable to create Inquiry - rejected by DB.";

                    array_push($createResult->data, $this->inq);                
                }
            }
            echo json_encode($createResult);
        }
        //
        //  read    REST GET operation
        //
        function read($id){
            
            $inquiryResult = new restResponse();

            $whereClause = " ";

            if($id > 0){
                $whereClause = "WHERE id = $id"; // remember to insert varaibles into a string you must use double quotes THIS WILL NOT WORK -> 'WHERE id = $id'
                $inquiryResult->message = "Read operation using criteria: $whereClause";
			}
            else{
                $inquiryResult->message = "Read operation using criteria: ALL";
			}

            $result = $this->inq->select($whereClause);

            $count = $result->rowCount();
  
            // check if more than 0 record found
            if($count < 1){
                // set response code - 404 Not found
                http_response_code(404);
  
                $inquiryResult->status = "Not Found";

                // tell the user no products found

                echo json_encode($inquiryResult);

                return;
            }

            // retrieve our table contents
            // fetch() is faster than fetchAll()
            // http://stackoverflow.com/questions/2770630/pdofetchall-vs-pdofetch-in-a-loop
        
            while ($row = $result->fetch(PDO::FETCH_ASSOC)){
                
                //  'extract($row)' will create and initialize a variable '$name' for each $row['name'] entry in the associative array ($row) 
                //  returned by PDO::fetch(PDO::FETCH_ASSOC)

                extract($row);
  
                $inquiry_item = array(
                                "id" => $id,
                                "name" => $name,
                                "phone" => $phone,
                                "request" => html_entity_decode($request),
                                "created" => $created,
                                "modified" => $modified
                );
                array_push($inquiryResult->data, $inquiry_item);
            }
            $inquiryResult->status = "success";
            $inquiryResult->count = $count;
            
            // set response code - 200 OK
            http_response_code(200);
  
            echo json_encode($inquiryResult);
		}

        //
        //  update    REST PUT operation
        //
        function update($id){
            $updateResult = new restResponse();
		    
            $updateResult->message = "update operation criteria : id = $id";
            
            array_push($updateResult->data, $this->inq);

            if(! $this->validInquiry){
                // set response code - 400 bad request
                http_response_code(400);
  
                $updateResult->status = "error";
                $updateResult->message = "Required Inquiry data not provided.";
            }
            else{
                if($id > 0){
                    $affectedCount = $this->inq->update($id);
                    $updateResult->count = $affectedCount;
                    $updateResult->status = "success";
                // set response code - 200 OK
                    http_response_code(200);

                }
                else{
                    $updateResult->message = "update operation with NO criteria";

                    http_response_code(403);        // forbiden
                    $updateResult->status = "error";
			    }
            }  
            echo json_encode($updateResult);
        }

        //
        //  delete    REST DELETE operation
        //
        function delete($id){
            $deleteResult = new restResponse();

            // Not requiring 'valid Inquiry data' to be submitted for delete operation
            //if($this->validInquiry){
                array_push($deleteResult->data, $this->inq);
            //}
            
            if($id > 0){
                $whereClause = "WHERE id = $id"; // remember to insert varaibles into a string you must use double quotes THIS WILL NOT WORK -> 'WHERE id = $id'

                $deleteResult->message = "delete operation with criteria: $whereClause";
                $affectedCount = $this->inq->delete($id);
                
                $deleteResult->count = $affectedCount;
                
                if($affectedCount == 1){
                    http_response_code(200);
                    $deleteResult->status = "success";
                }
                else{
                    http_response_code(400);
                    $deleteResult->status = "failed";
				}

			}
            else{
                $deleteResult->message = "delete operation with NO criteria";

                http_response_code(403);        // forbiden
                $deleteResult->status = "error";
			}
  
            echo json_encode($deleteResult);
		}

    } /* class InquiryController */
?>




