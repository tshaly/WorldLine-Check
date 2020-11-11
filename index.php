<?php

//--------Refresh and Get Token----------------
function generate_access_token() {
  $post = [
      'refresh_token'  => '1000.13000773cbea6140a315ce360523767a.33f769c121e6197f78e85ab2f2f3a0c9',
      'client_id'      => '1000.KQ1T9Z8W0DG4WA5QJF00SWZ5YPT3XU',
      'client_secret'  => '368730ce5cc77f62a8ae865445b532a341be98c5f6',
      'grant_type'     => 'refresh_token'
  ];
  $ch = curl_init();
  curl_setopt( $ch, CURLOPT_URL, "https://accounts.zoho.com/oauth/v2/token" );
  curl_setopt( $ch, CURLOPT_POST, 1 ); 
  curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $post ) );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/x-www-form-urlencoded') );

  $responseToken = curl_exec( $ch );
  //$response = json_encode( $response );
  $responseToken1 = json_decode( $responseToken, true );

  //return respone
  global $Access_token;
  $Access_token = $responseToken1['access_token'];

  

}

generate_access_token();
//End generate Token--


//----------------get records from ZOHO CRM-----------------

function get_records() {
  global $Access_token;
    //$Access_token1 = '1000.f710e5218c4d29c7cb2df71b5334c398.03143131c6b4c230cbc2bdb8b3722b2f';
   
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, "https://www.zohoapis.com/crm/v2/Leads?cvid=1272796000033605046" );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Zoho-oauthtoken ' . $Access_token ,
         'Content-Type: application/x-www-form-urlencoded'
         ) );

    $response = curl_exec( $ch );
   // $response = json_decode( $response );
    $response1 = json_decode( $response, true );
    
    global $Leadid;
    global $Leadvat;
    global $LeadOwner;


   $Leadid = $response1['data'][0]['id'];
   $Leadvat = $response1['data'][0]['N_entreprise'];
   $LeadOwner = $response1['data'][0]['Owner'];
  
}
get_records();
//--End fonction get_record---------


//--------- Recherche chez WL--------
function search_client() {
  global $Leadvat;
 
  //Do a search to WL with N_entreprise
if(!empty($Leadvat)){
    
  $vat = ['vat' => $Leadvat,];
    $post_data = [
      'jsonrpc'  => '2.0',
      'id'      => '9999',
      'method'  => 'customer_search',
      
      'params' => $vat,
     
  ];
  $ch = curl_init();
  curl_setopt( $ch, CURLOPT_URL, "https://msonboarding.eae.apis.svc.as8677.net/wln/partner/api/1.0/jsonrpc" );
  curl_setopt( $ch, CURLOPT_POST, 1 ); 
  curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $post_data ) );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Basic aW5vY3JlYV90ZWNobmljYWw6SW5ub2NyZWFAMjAyMA==',
     'Content-Type: application/json-rpc'
     ) );
  
     $response = curl_exec( $ch );
     $response1 = json_decode( $response, true );
    
     global $WLEmail;
     global $WLId;
     global $WLSegments;
     global $WLIban;
     $WLId = $response1['result']['customers'][0]['id'];
     $WLSegments = $response1['result']['customers'][0]['sales_info']['sales_segment'];
     $WLIban = $response1['result']['customers'][0]['iban_debit'];
     $WLEmail = (string) $response1['result']['customers'][0]['preferred_contact']['email'];
    
    //End Fin num trouvé

}
else{
echo"Aucun numéro trouvé";

}
//End search
  
}
  
search_client();
//-------End fonction  Search

//----------------Update Record in ZOHO CRM
function update_records() {
  global $WLId;
  global $Access_token;
  global $Leadid;
  global $WLEmail;
  global $WLSegments;
  global $WLIban;
  //If Empty
 if(!empty($WLId)){
  $header = array(
    "Authorization: Zoho-oauthtoken " . $Access_token ,);
  
  
  $post_data = array(
    'data' => array(
      array(
        "id"  => $Leadid,
        "WL_Existe"  => "Yes",
        "Segments"  =>$WLSegments,
        "WL_Email"  => $WLEmail,
        "WL_iban"  => $WLIban,
        "WL_Check"  => "1"
      ),
    
    ),
      'trigger' => array(),
  );
  $data_json = json_encode($post_data);  

$ch = curl_init();
curl_setopt( $ch, CURLOPT_URL, "https://www.zohoapis.com/crm/v2/Leads" );
curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "PUT" );
curl_setopt( $ch, CURLOPT_POST, 1);
curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_json );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);


   $response = curl_exec( $ch );
   curl_close($ch);
   
    echo '<br />Existe et enregistré ';
   
  }

  // If No exist in WL
  else{
    $header = array(
      "Authorization: Zoho-oauthtoken " . $Access_token ,);
    
    
    $post_data = array(
      'data' => array(
        array(
          "id"  => $Leadid,
          "WL_Existe"  => "No",
          "WL_Check"  => "1"
        ),
      
      ),
        'trigger' => array(),
    );
    $data_json = json_encode($post_data);  
  
  $ch = curl_init();
  curl_setopt( $ch, CURLOPT_URL, "https://www.zohoapis.com/crm/v2/Leads" );
  curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
  curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "PUT" );
  curl_setopt( $ch, CURLOPT_POST, 1);
  curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_json );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
  
  
     $response = curl_exec( $ch );
     curl_close($ch);
     echo '<br /> Existe pas et enregistré ';
  }
  //--End update
}
update_records();
//---End fonction uodate








?>