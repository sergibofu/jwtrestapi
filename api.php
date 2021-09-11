<?php 
// use Firebase\JWT\JWT;

class Api extends Rest{
    public $dbConn;

    public function __construct(){
        
        //we execute the parent constructor
        parent::__construct();

        //we connect to our db
        $db = new DbConnect();
        $this->dbConn = $db->connect();





    }

    public function generateToken(){

        try{
        //first we validate that our parameters are correct
        $email = $this->validateParameter('email', $this->param['email'], STRING);
        $pass = $this->validateParameter('pass', $this->param['pass'], STRING);

        //we prepare our statement and bind our parameters
        $stmt = $this->dbConn->prepare("SELECT * FROM users WHERE email = :email AND password = :pass");
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":pass", $pass);

        //we execute our params
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        //if the response is not an array(hence, the email or pass is not correct), we throw an error
        if(!is_array($user)){
            $this->throwError(INVALID_USER_PASS, "Email or password are incorrect");
        }

        //if the user is not active, we throw an error
        if($user['active'] == 0){
             $this->throwError(USER_NOT_ACTIVE, "The user is not active");
        }
        
        //we prepare the payload for the jwt codification
        $payload = [
            'iat'=> time(),
            'iss'=>'localhost',
            'exp'=>time() + 10*(60),
            'userId' => $user['id']
        ];

        //we encode our data
         $token = Firebase\JWT\JWT::encode($payload, SECRETE_KEY);
         $data = ['token'=>$token];

         //we send our response
         $this->returnResponse(SUCCESS_RESPONSE, $data);

        }catch(Exception $e){
            $this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());
        }

    }

    public function addCustomer(){
        //we validate our params
         $name = $this->validateParameter('name', $this->param['name'], STRING);
         $email = $this->validateParameter('email', $this->param['email'], STRING);
         $addr = $this->validateParameter('addr', $this->param['addr'], STRING);
         $mobile = $this->validateParameter('mobile', $this->param['mobile'], STRING);

    

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

            //we create a customer object in order to insert the new customer into the database
            $cust = new Customer();

            //we set our variables inside the customer class
            $cust->setName($name);
            $cust->setEmail($email);
            $cust->setAddress($addr);
            $cust->setMobile($mobile);
            $cust->setCreatedBy($payload->userId);
            $cust->setCreatedOn(date('Y-m-d'));

            //we insert our customer and return our response
            if(!$cust->insert()){
                $message = "Failed to insert";
            } else{
                $message = "Inserted correctly";
            }

            $this->returnResponse(SUCCESS_RESPONSE, $message);
         } catch(Exception $e){
            $this->throwError(ACCESS_TOKEN_ERRORS, $e->getMessage());
         }

    }

    public function updateCustomer(){
        //we validate our params
        $customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);
        $name = $this->validateParameter('name', $this->param['name'], STRING);
        $addr = $this->validateParameter('addr', $this->param['addr'], STRING);
        $mobile = $this->validateParameter('mobile', $this->param['mobile'], STRING);


        //we validate our token
        $this->validateToken();

        //we create a customer object in order to insert the new customer into the database
        $cust = new Customer();

        //we set our variables inside the customer class
        $cust->setId($this->userId);
        $cust->setName($name);
        $cust->setAddress($addr);
        $cust->setMobile($mobile);
        $cust->setUpdatedBy($this->userId);
        $cust->setUpdatedOn(date('Y-m-d'));
        
        //we insert our customer and return our response
        if(!$cust->update()){
            $message = "Failed to update";
        } else{
             $message = "Updated correctly";
        }

        $this->returnResponse(SUCCESS_RESPONSE, $message);

        
    }

    public function deleteCustomerDetails(){
        $customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);

        //we validate our token
        $this->validateToken();

        $cust = new Customer();

        $cust->setId($customerId);

        if(!$cust->delete()){
            $message = "Failed to delete";
        } else{
            $message = "Deleted correctly";
        }

        $this->returnResponse(SUCCESS_RESPONSE, $message);
    }

}


?>