<?php 

    require_once('constants.php');
    require_once('php-jwt-master\src\JWT.php');


    class Rest{
        protected $request;
        protected $serviceName;
        protected $param;
        protected $userId;

        public function __construct(){

            //if the request method is not post, we throw an error
            if($_SERVER['REQUEST_METHOD'] !== "POST"){
                //if the requesat method is not valid, we call the throwError function
                $this->throwError(REQUEST_METHOD_NOT_VALID, 'Request method is not valid');
            }

            //first we read our input
            $file = fopen("php://input", 'r');

            //we store our input file in a string and we stored on the variable $request
            $this->request = stream_get_contents($file);

            //we validate the request
            $this->validateRequest($this->request);
            


        }

        public function validateRequest(){
            //if content type is not application/json then we throw an error
            if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
                $this->throwError(REQUEST_CONTENTTYPE_NOT_VALID, 'Request content type not valid');
            }

            //we decode the json data
            $data = json_decode($this->request, true);

            //we check if the nam of the api is send
            if(!isset($data['name']) || $data['name'] == ''){
                $this->throwError(API_NAME_REQUIRED, "API name required");
            }
            $this->serviceName = $data['name'];

            //we check if the params are send 
            if(!is_array($data['param'])){
                $this->throwError(API_PARAM_REQUIRED, "API PARAM is required");
            }
            $this->param = $data['param'];

        }

        public function processApi(){
            /*each of our apis that inherit from the rest parent class should contain one function with
            the same name as our serviceName that we will check here */

            //we create a new child object api
            $api = new Api();
            
            //we create a reflection method
            $rm = new reflectionMethod($api, $this->serviceName);

            //if the method doesn't exist,we throw an exception
            if(!method_exists($api, $this->serviceName)){
                 $this->throwError(API_DOST_NOT_EXIST, "Api does not exist");
            }

            //we invoke the method
            $rm->invoke($api);
        }   

        public function validateParameter($fieldName, $value, $dataType, $required = true){
            //if the value is empty we throw an exception
            if($required == true && empty($value) == true){
                $this->throwError(VALIDATE_PARAMETER_REQUIRED, "$fieldName Parameter is required");
            }

            //we check if the type is valid
            switch($dataType){
                case BOOLEAN:
                    if(!is_bool($value)){
                        $this->throwError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for $fieldName It should be boolean");
                    }
                break;
                case INTEGER:
                    if(!is_numeric($value)){
                        $this->throwError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for $fieldName It should be integer");
                    }
                break;
                case STRING:
                    if(!is_string($value)){
                        $this->throwError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for $fieldName It should be string");
                    }
                break;
            }

            //if the parameter is correct, we return its value
            return $value;
        }

        public function throwError($code, $message){
            //we set the response to json
            header("content-type: application/json");

            //we encode our data in json format
            $errorMsg = json_encode(["status"=>$code, "message"=>$message]);

            //we return the error code and message to the client in json format
            echo $errorMsg; exit;
        }

        public function returnResponse($code, $data){
            //we set the response to json
            header("content-type: application/json");

            //wel encode our data into json format
            $response = json_encode(['response'=>['status'=>$code, 'result'=>$data]]);

            //we return the code and the daata in json format
            echo $response; exit;
        }

        public function getAuthorizationHeader(){
            $headers = null;
            if (isset($_SERVER['Authorization'])) {
                $headers = trim($_SERVER["Authorization"]);
            }
            else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
                $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
            } elseif (function_exists('apache_request_headers')) {
                $requestHeaders = apache_request_headers();
                // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
                $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
                if (isset($requestHeaders['Authorization'])) {
                    $headers = trim($requestHeaders['Authorization']);
                }
            }
            return $headers;
        }

	    public function getBearerToken() {
	        $headers = $this->getAuthorizationHeader();
	        // HEADER: Get the access token from the header
	        if (!empty($headers)) {
	            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
	                return $matches[1];
	            }
	        }
	        $this->throwError( ATHORIZATION_HEADER_NOT_FOUND, 'Access Token Not found');
	    }

        public function validateToken(){

            try{
                //we extract our token form the header
                $token = $this->getBearerToken();
   
                //we decode our payload
                $payload = Firebase\JWT\JWT::decode($token, SECRETE_KEY, ['HS256']);
   
   
               //we prepare our statement and bind our parameters
               $stmt = $this->dbConn->prepare("SELECT * FROM users WHERE id = :id");
               $stmt->bindParam(":id", $payload->userId);
    
   
               //we execute our params
               $stmt->execute();
               $user = $stmt->fetch(PDO::FETCH_ASSOC);
   
               //we check if the id has matched with some user
               if(!is_array($user)){
                   $this->throwError(INVALID_USER_PASS, "This user isn't found on our database");
               }
   
               //we check if the user is activated
               if($user['active'] == 0){
                   $this->throwError(USER_NOT_ACTIVE, "User is not activated. Please contact to admin");
               }

               //we set our global id variable to the id reived
               $this->userId = $payload->userId;
            } catch(Exception $e){
               $this->throwError(ACCESS_TOKEN_ERRORS, $e->getMessage());
            }
        }

    }

?>