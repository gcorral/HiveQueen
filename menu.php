<?php

 session_start();

 if (!isset($_SESSION['username'])){
   header('Location:index.php');
 }

?>

<html>
<head>

    <title></title>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script type="text/javascript" src="functions.php"></script>
 
<style type="text/css">
    
    .tab {
	padding-top: 7%;
    }
    .pater > div {
	display: inline-block;
	width: 25%;
    }
    .messages{
        float: center;
        font-family: sans-serif;
        display: none;
    }
    .info{
        padding: 10px;
        border-radius: 10px;
        background: orange;
        color: #fff;
        font-size: 18px;
        text-align: center;
    }
    .before{
        padding: 10px;
        border-radius: 10px;
        background: blue;
        color: #fff;
        font-size: 18px;
        text-align: center;
    }
    .success{
        padding: 10px;
        border-radius: 10px;
        background: green;
        color: #fff;
        font-size: 18px;
        text-align: center;
    }
    .error{
        padding: 10px;
        border-radius: 10px;
        background: red;
        color: #fff;
        font-size: 18px;
        text-align: center;
    }
</style>
</head>
<body>

    <table width=100% style='background-color:#D5F4F3;'><tr>
	<td align=left width=20% style='font-size:17px;background-color:#D5F4F3;'>
	<img width=75px src=images/logoetl.png style=" padding: 5px 0px 0px 10px;">
	<td align=center width=60% style='font-size:25px;background-color:#D5F4F3;'>
	<p>Servicio de Impresión</p>
	<td align=right width=20% style='font-size:17px;background-color:#D5F4F3;color:#000000;'>
	<p id="user"></p>
	<button id='close'>Cerrar Sesión</button>
    </tr></table>    

    <div align=center>
	<p>Archivos soportados: PDF, ODT, DOC, DOCX, PS y Plain Text</p>
    </div>

    <table class="tab" width=100%><tr>
	<td align=center style='font-size:17px;width:33%'>
    	<!--el enctype debe soportar subida de archivos con multipart/form-data-->
    	<form id="form" enctype="multipart/form-data" class="formulario">
        	<label>1. Seleccionar un archivo</label><br>
        	<br>
		<input name="archivo" type="file" id="imagen" />
	<td align=center style='font-size:17px;width:33%'>
	    <label>2. Seleccionar Impresora</label><br>
	    <br>
	    <select id="impr" name="Impresora">
	    <option value="Arcoiris" selected>Arcoiris (4.1.F09)</option>
	    <option value="Arlequin">Arlequin (4.1.F02)</option>
	    <option value="Blanco">Blanco (4.1.C03)</option>
	    <option value="Cigarra">Cigarra (4.1.C01)</option>
	    <option value="Escarlata" >Escarlata (4.1.A03)</option>
	    <option value="Octarino">Octarino (4.1.F09)</option>
	    <option value="Prisma">Prisma (4.1.A01)</option>
	    <option value="Verde">Verde (4.0.F03)</option>
	    </select>
	    <br><br><br>
	    <div class="pater">
	    	<div>
		<input class="Selection" id="BN" type="radio" name="Color" value="BN" checked></input>
		<label class="Selection">BN</label><br>
		<input class="Selection" id="color" type="radio" name="Color" value="Color"></input>
		<label class="Selection" id="colorL">Color</label>
		</div>
		<div>
		<input class="Selection" id="Duplex" type="radio" name="Pag" value="Duplex" checked>
		<label class="Selection">Duplex</label><br>
		<input class="Selection" id="Single" type="radio" name="Pag" value="Single" >
		<label class="Selection">Single</label>
		</select>
		</div>
		<div>
		<label class="Selection">Número de Copias</label><br>
		<input class="Selection" type="number" name="num" id="num" min="1" max="25" value="1"/>
		</div>
	    </div>
	<td align=center style='font-size:17px;width:33%'>
	    <input type="button" id="subir" value="Imprimir archivo" />
	    </form>
    </tr></table>

    <br>
    <br>

    <!--div para visualizar mensajes-->
    <div align=center class="messages"></div><br /><br />


</body>
</html>
