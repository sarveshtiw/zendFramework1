<?php
class FacebookController extends Zend_Controller_Action{
    
    public function init(){
        
        require_once realpath(dirname(__FILE__)).'/../../library/facebooksdk/src/facebook.php'; 
    
        $this->fbAppId = "266446760141219";
        $this->fbsecret = "1334d98b576f2a9c668ed101a0fe1179";
//        $this->fbAppId      = "257955024398140";
//        $this->fbsecret     = "37c0762eddc12332c410a8552b25c939";
        $this->callback_url = "http://wa.appstudioz.fr/";
    }
    
    public function friendlistAction(){
        $facebookObj = new Facebook(array(
             'appId' => $this->fbAppId,
             'secret' => $this->fbsecret,
             'cookie' => true,
        ));
        
        $fbAccessToken = "CAACEdEose0cBANZBUDwQBaRWUocyigX3lVin2NKbFKPuGUNpAeZBaTuunHi5qZCUZBFnKejUA40ZBbrgFjwN8vxnW44RojSv8V5zS5zRPqCrqaZA1ZBpxR8jZBstvWpHyZCBPI5Ltx9L3oIL7wl04JQvsCZBlfwU0Mwyt5UJZA5OyVOlSKc5UhFsDHgsnTJ4EF3AkKLt51W599r8QZDZD";
        
        $facebookObj->setExtendedAccessToken($fbAccessToken);
    //    $token = $facebookObj->getAccessToken();
        
        $user_profile = $facebookObj->api('/me');
        echo "12";exit;
        print_r($user_profile);exit;
         $friends = $facebookObj->api('/me/friends', 
                          'GET', 
                           array (
                             'access_token' => $fbAccessToken));
        
         print_r($friends);exit;
        
    }
    
     public function testfbmessageAction(){
          
         $facebookObj = new Facebook(array(
             'appId' => $this->fbAppId,
             'secret' => $this->fbsecret,
             'cookie' => true,
        ));
        
         
        $fbAccessToken =   " CAADqm9WY5zwBAFKoZBtMXvnIni1qTuZCOSlPyW6QX0ezFGRObNJYAqgW8PeEVumgs07RThRXOSVqK2tuAcwPxVPTXxUXwwLdiRW6IVgnUT9NACRfDiGJu9pYlYuLUQ6KOZBCCDJIpIqdDXybU0bexfr2rj9KwtsHNZADFZBLNALAd3BSAmGZCxCX89mXNBCIrqcMELPEyNpgh9YUXmCL7ux1ZC2xMSgadVgM8ocOVXekQZDZD";
 //       $facebookObj->setAccessToken($fbAccessToken);
        $message = "hello";
        
        $fr_url = 'https://graph.facebook.com/778291975534960/feed?access_token='.$fbAccessToken.'&message='.$message;
        
        
        
        try {
            $statusUpdate = $facebookObj->api('/me/feed', 'post', array('message'=> $message, 'access_token' => $fbAccessToken));
        } catch (FacebookApiException $e) {
            print_r($e);exit;
        }
        echo $statusUpdate;exit;
     }
    
     public function facebookFriendListAction(){
         
         $facebookObj = new Facebook(array(
             'appId' => $this->fbAppId,
             'secret' => $this->fbsecret,
             'cookie' => true,
        ));
        
         
        $fbAccessToken =   "CAACEdEose0cBAHzuvrzLPSl84ZCz66py4aOJzjbD9ZAhXvEkUpEkqY1nETzZBtP9TaDKoPHim4Sj6DccCuLHLQiW0NWqn49zmaZAVS0ah8FcaeEhVOkPyBmLl4cAjc0jvF4CKY5kcvMFrfTsfqvVkq2pSAb88GDSoKm0AMh8CWJ13ZAEcjwN23zBYYC44hK9o9C9Yqb6kfwZDZD";
        $facebookObj->setAccessToken($fbAccessToken);
        $fr_url = 'https://graph.facebook.com/me/friends?access_token='.$fbAccessToken.'&fields=id,name,email,picture.width(100).height(100).type(large)';
        
        $fr_json = file_get_contents($fr_url);
            
            
        $friendResponseSet = json_decode($fr_json,true);
        
        print_r($friendResponseSet);exit;
        
        try{
            
            $facebookObj->setExtendedAccessToken($fbAccessToken);
            
           
            echo $fr_url;exit;
            
            
            
            $user_profile = $facebookObj->api('/me');
            
            $userId = $user_profile['id']; 
            
            $fql = "SELECT uid, first_name, last_name FROM user "
                    . "WHERE uid in (SELECT uid2 FROM friend where uid1 = $userId)";
            
             $friends = $facebookObj->api(array(
                 'method'       => 'fql.query',
                 'access_token' => $facebookObj->getAccessToken(),
                 'query'        => $fql,
               ));
            
            
            
           
            
            
            
            
            echo "<pre>";
            print_r($friendResponseSet);
           
            echo $friendResponseSet['paging']['next'];exit;
            
            print_r($friendResponseSet);exit;
            
            $friends = $facebookObj->api(array(
                "method"        => "fql.query",
                "query"        => "SELECT name FROM user WHERE uid IN (SELECT uid2 FROM friend WHERE uid1 = me())"
            ));
            
            echo "<pre>";
            print_r($friends);exit;
            
            $friendData = $friendResponseSet['data'];
            
            echo "<pre>";
            print_r($friendData);exit;
            
            $responseSet = array();
            
            foreach($friendData as $friendRow){
                $responseSet[] = array(
                    'userFbId'  => $friendRow['id'],
                    'name'      => $friendRow['name'],
                    'picture'   => (isset($friendRow['picture']) && isset($friendRow['picture']['data'])) ? $friendRow['picture']['data']['url']:''
                );
            }
            
            echo "<pre>";
            print_r($responseSet);
            exit;
            
            
            $friends = $facebookObj->api(array(
                "method"        => "fql.query",
                "query"         => "SELECT uid,name FROM user WHERE uid IN (SELECT uid2 FROM friend WHERE uid1 = me())"
            ));
            
            echo "<pre>";
            print_r($friends);exit;
            
            
            
            echo $fbAccessToken."<br>";
           
        }catch(Exception $e){
            echo $e->getMessage();exit;
        }
        
        
    }
    
    public function fbloginAction(){

    }
     public function postFbMessageAction(){
        $app_id = $this->fbAppId;
        $app_secret = $this->fbsecret; 
        $my_url = $this->callback_url; 
        $video_title = "YOUR_VIDEO_TITLE";
        $video_desc = "YOUR_VIDEO_DESCRIPTION";

        $code = $_REQUEST["code"];
   
        if(empty($code)) {
           $dialog_url = "http://www.facebook.com/dialog/oauth?client_id=" 
             . $app_id . "&redirect_uri=" . urlencode($my_url) 
             . "&scope=publish_stream";
            echo("<script>top.location.href='" . $dialog_url . "'</script>");
        }

        $token_url = "https://graph.facebook.com/oauth/access_token?client_id="
            . $app_id . "&redirect_uri=" . urlencode($my_url) 
            . "&client_secret=" . $app_secret 
            . "&code=" . $code;
        $access_token = file_get_contents($token_url);

        $post_url = "https://graph-video.facebook.com/me/videos?"
            . "title=" . $video_title. "&description=" . $video_desc 
            . "&". $access_token;
        
$post_url = "https://graph.facebook.com/". "? batch=". urlencode($batched_request). "&access_token=". $access_token. "&method=post";

        echo '<form enctype="multipart/form-data" action=" '.$post_url.' "  
             method="POST">';
        echo 'Please choose a file:';
        echo '<input name="file" type="file">';
        echo '<input type="submit" value="Upload" />';

        echo '</form>';
        
     }
}

?>
