<?php

class FTP {
    
    private $toIgnore;
    
    private $server;
	private $username; 
	private $password;
	
	private $connection;
	private $serverContent;
	private $serverContentUpdated;
	private $pwd;
	private $pwdChanged;
	
	function __construct($_server, $_username, $_password){
	    $this->toIgnore = ($_server == "demo" || $_username == "demo" || $_password == "demo") ? true : false;
	    
		$this->server = $_server;
		$this->username = $_username;
		$this->password = $_password;
		
		$this->pwd = "";
		$this->pwdChanged = true;
		$this->serverContentUpdated = true;
		
	}
	
	private function connect() {
	    if($this->toIgnore) { return false; }
	    
        try {
            $ftpConn = ftp_connect($this->server);
            if (false === $ftpConn) {
                throw new Exception('Unable to connect');
                return false;
                
            } 
            $loggedIn = ftp_login($ftpConn,  $this->username,  $this->password);   
            if (false === $loggedIn) {
                throw new Exception('Unable to log in');
                return false;
                
            }
            
        } catch (Exception $error) {
            echo "Failure (" . $this->server .  "): " . $error->getMessage();
            return false;
            
        }
        
        $this->connection = $ftpConn;
        return true;
        
	}
	
	private function disconnect() {
	    if(isset($this->connection) && $this->connection != null)
	        ftp_close($this->connection);
	    
	}
	
	function removeExtension($name) {
	    return substr($name,0,-strpos(strrev($name),".") - 1);
	    
	}
	
	function getExtension($name) {
	    return pathinfo($name, PATHINFO_EXTENSION);
	    
	}
	
	function deleteFile($fileName, $filePath = null) {
	    if($this->toIgnore) { return false; }
	    if(!$this->connect()) { return; }
	    
	    if($this->pwd != $filePath && $filePath != null) {
	        $this->pwd = $filePath; 
	        $this->pwdChanged = true;
	        
	    }
	    
	    $this->serverContentUpdated = ftp_delete($this->connection, $this->pwd . $fileName);
	    echo $this->serverContentUpdated ;
	    $this->disconnect();
	    
	}
	
	function uploadFile($fileName, $fileOriginalPath, $fileDestinyPath = null, $deleteOriginal = false) {
	    if($this->toIgnore) { return false; }
	    if(!$this->connect()) { return; }
	    
	    $ext =  '.' . $this->getExtension($fileOriginalPath);
	    if($this->getExtension($fileName) != null)
	        $fileName = $this->removeExtension($fileName);
	     
	     
	    if($this->pwd != $fileDestinyPath && $fileDestinyPath != null) {
	        $this->pwd = $fileDestinyPath; 
	        $this->pwdChanged = true;
	        
	    }   
	    
	    ftp_pasv($this->connection, true);
	    $feedback = ftp_nb_put($this->connection, $this->pwd . $fileName . $ext, $fileOriginalPath, FTP_BINARY);
	    
	    while ($feedback == FTP_MOREDATA) {
            $feedback = ftp_nb_continue($this->connection);
            
        }
        
        if ($feedback != FTP_FINISHED || $feedback == FTP_FAILED) {
            $deleteOriginal = false;
            echo("Upload failed (" . $feedback . "): " . $fileName . "<br>");
            
        }
        
        if(!$this->pwdChanged)
            $this->serverContentUpdated = true;
            
        
        echo("Upload finished: " . $fileName  . $ext . "<br>"); 
        
        if($deleteOriginal) {
            unlink("../tempImages/" . $fileName  . $ext); //Not dynamic
            echo("File deleted: " . $fileName  . $ext . "<br><br>"); 
            
        }
            
	    
	    ftp_pasv($this->connection, false);
	    $this->disconnect();
	    
	    
	    //OUTRO MÉTODO
	    /*$ext =  '.' . $this->getExtension($fileOriginalPath);
	    //Exemplo: fopen("ftp://nor267@exemplo.com:senha@exemplo.com/imagens/novaImg.jpg", "wb")
        $destination = fopen("ftp://" . $this->username . ":" . $this->password . "@" . str_replace("ftp.", "", $this->server) . "/" . $filePath . $fileName . $ext, "wb");
        $source = file_get_contents($fileOriginalPath);
        fwrite($destination, $source, strlen($source));
        fclose($destination);
        */
            
        
        
	}
	
	function createFile($fileName, $fileContent, $filePath = null) { //fileName incluir extenção
	    if($this->toIgnore) { return false; }
	    if(!$this->connect()) { return; }
	    
	    if($this->pwd != $filePath && $filePath != null) {
	        $this->pwd = $filePath; 
	        $this->pwdChanged = true;
	        
	    }
	    
	    $destination = fopen("ftp://" . $this->username . ":" . $this->password . "@" . str_replace("ftp.", "", $this->server) . "/" . $this->pwd . $fileName . $ext, "wb");
        fwrite($destination, $fileContent, strlen($fileContent));
        fclose($destination);
        
	    $this->disconnect();
	    
	}
	
	function renameFile($oldName, $newName) { //Incluir "path" se fôr o caso e extenção, funciona também para mover o ficheiro
	    if($this->toIgnore) { return false; }
	    if(!$this->connect()) { return; }
	    ftp_rename($this->connection, $oldName, $newName);
	    
	    $this->disconnect();
	
	}
	
	function fileExists($fileName, $directory = null) {
	    if($this->toIgnore) { return false; }
	    
	    if(in_array(explode("-",$fileName)[0], ['24']))
	        return true;
	    
	    if($this->pwd != $directory && $directory != null) {
	        $this->pwd = $directory; 
	        $this->pwdChanged = true;
	        
	    }
	        
	    if($this->pwdChanged || $this->serverContentUpdated) {
	        if(!$this->connect()) { return false; }
            ftp_pasv($this->connection, true);  
	        $this->serverContent = ftp_nlist($this->connection, $this->pwd);
	        ftp_pasv($this->connection, false);
	        $this->disconnect();
	        $this->pwdChanged = false;
	        $this->serverContentUpdated = false;
	        
	    } 
	     
	    return in_array($this->pwd . $fileName, $this->serverContent);
	    
        
    }
	
}
?>