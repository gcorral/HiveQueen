<?php

$usererror = $passerror = $notfounderror = "";

session_start();

if($_POST['submit'] == 'Submit')
{
    if(empty($_POST['username']))
    {
        $usererror = "Authentication is required";
    }

    if(empty($_POST['password']))
    {
	$passerror="Authentication is required";
    }
     
    $zonas = array (
              array ('type' => '1', 
                     'server' => 'ldap://ldap.it.uc3m.es', 
                     'basedn' => 'dc=it,dc=uc3m,dc=es'
                    ),
                   );

    for ($i = 0, $size  = count($zonas); $i < $size; ++$i) {

      $server = $zonas[$i]['server'];
      $ldap_base_dn = $zonas[$i]['basedn'];


      $ldapconn = ldap_connect($server) or die("Could not connect to LDAPit server.");

      // Set some ldap options for talking to
      ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
      ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

      if ($ldapconn) {

         $ccid=$_POST['username'];
         $filter="(&(uid=$ccid))";

         if (!($result = ldap_search($ldapconn, $ldap_base_dn, $filter))) {
                die("Unable to search ldap server");
         }

         if ( ldap_count_entries($ldapconn,$result) == 1 ) {
                $info = ldap_get_entries($ldapconn, $result);
                $ldaprdn  = $info[0]["dn"];     // ldap rdn or dn
                $ldappass = $_POST['password'];  // associated password

                $ldapbind = @ldap_bind($ldapconn, $ldaprdn, $ldappass);

                if ($ldapbind) {
                   echo "LDAP bind successful...\n";
                   ldap_close($ldapconn);
                   $_SESSION['username']=$ccid;
                   $_SESSION['password']=$ldappass;

                   header( 'Location: menu.php' ) ; 

                }
		else{
			if(!empty($_POST['username']) && !empty($_POST['password'])){
				$notfounderror="Invalid Username or Password";
			}
		}	     
           }
	   else{

		if(!empty($_POST['username']) && !empty($_POST['password'])){
			$notfounderror="Invalid Username or Password";
		}

	   }
	
        ldap_close($ldapconn);

        }
     }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Hive Queen</title>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
 
<style type="text/css">
   #logoetl {
	margin-left: 45%;
   }	
   #user {
	margin-left: 43%;
   } 
   #pass {
	margin-left: 43%;
   }
   #submit {
	margin-left: 47%;
   }
   span {
	color:red;
   }

</style>
</head>

<body>

    <table width=100% style='background-color:#D5F4F3;'>
      <tr>

	<td align=left width=20% style='font-size:17px;background-color:#D5F4F3;'>

        <img src=images/logoetl.png alt="ETL" style=" padding: 5px 0px 0px 10px;width:75px"> 
        <!-- <img src=images/queen-bee-162026_960_720.png alt="ETL" style=" padding: 5px 0px 0px 10px;width:75px" --> 

	<td align=center width=60% style='font-size:25px;background-color:#D5F4F3;'>

	<p>Hive Queen</p>

        <p tyle="font-size:14px;background-color:#D5F4F3;">Web interface tool for Linux client administration based on ansible</p>

	<td align=right width=20% style='font-size:17px;background-color:#D5F4F3;color:#000000;'>

      </tr>
    </table> 

    <br>

    <!--el enctype debe soportar subida de archivos con multipart/form-data-->
    <form id='login' action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method='post' accept-charset='UTF-8'>
    
     <fieldset >
 
     <!-- <legend>Login</legend> -->

     <input type='hidden' name='submitted' id='submitted' value='1'/> 
 
     <!-- <img id='logoetl' src="images/logoetl.png" alt="HTML5 Icon" style="width:200px;height:200px;"> -->
     <img id='logoetl' src="images/queen-bee-162026_960_720.png" alt="HTML5 Icon" style="width:200px;height:200px;"> 

     <br>

     <label id='user' for='username' >UserName:</label>

     <input type='text' name='username' id='username'  maxlength="50" />

     <span class = "error">* <?php echo $usererror; ?> </span>a 

     <br>
     <br>

     <label id='pass' for='password' >Password:</label>
    
     <input type='password' name='password' id='password' maxlength="50" />
    
     <span class= "error">* <?php echo $passerror;?> </span>
    
     <br>
     <br>
    
     <input id="submit" type='submit' name='submit' value='Submit' />
   
     <span class= "error"> <?php echo $notfounderror;?> </span>
    
      </fieldset>

    </form>
    
</body>
</html>
