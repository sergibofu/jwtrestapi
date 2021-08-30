<?php use Firebase\JWT\JWT;

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
        // if($user['active'] == 0){
        //     $this->throwError(USER_NOT_ACTIVE, "The user is not active");
        // }
        
        //we prepare the payload for the jwt codification
        $payload = [
            'iat'=> time(),
            'iss'=>'localhost',
            'exp'=>time() + 10*(60),
            'userId' => $user['id']
        ];

        //we encode our data
         $token = JWT::encode($payload, SECRETE_KEY);
         $data = ['token'=>$token];

         //we send our response
         $this->returnResponse(SUCCESS_RESPONSE, $data);

        }catch(Exception $e){
            $this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());
        }

    }

    public function addCustomer(){
         $name = $this->validateParameter('name', $this->param['name'], STRING);
         $email = $this->validateParameter('email', $this->param['email'], STRING);
         $addr = $this->validateParameter('addr', $this->param['addr'], STRING);
         $mobile = $this->validateParameter('mobile', $this->param['mobile'], STRING);

         try{
             echo $token = $this->getBearerToken();
             $payload = JWT::decode($token, SECRETEgi_KEY, ['HS256']);
             print_r($payload);
         } catch(Exception $e){
            $this->throwError(ACCESS_TOKEN_ERRORS, $e->getMessage());
         }

    }
}


?>