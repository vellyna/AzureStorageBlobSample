<?php
require_once 'vendor/autoload.php';
require_once "./random_string.php";

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

$connectionString = "DefaultEndpointsProtocol=https;AccountName=dicodingstoragevell;AccountKey=ACCOUNTKEY";

// Create blob client.
$blobClient = BlobRestProxy::createBlobService($connectionString);

if (isset($_POST['insert'])) {
    // Create container options object.
    $createContainerOptions = new CreateContainerOptions();
    $createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);

    // Set container metadata.
    $createContainerOptions->addMetaData("key1", "value1");
    $createContainerOptions->addMetaData("key2", "value2");

    $containerName = "dicodingcontainer".generateRandomString();
	
	$fileToUpload = $_FILES['fileToUpload']['name'];
	
    try {
        // Create container.
        $blobClient->createContainer($containerName, $createContainerOptions);

        // Getting local file so that we can upload it to Azure
        $myfile = fopen($_FILES["fileToUpload"]["tmp_name"], 'r') or die("Unable to open file!");
        fclose($myfile);
        
        # Upload file as a block blob
        
        $content = fopen($_FILES["fileToUpload"]["tmp_name"], 'r');

        //Upload blob
        $blobClient->createBlockBlob($containerName, $fileToUpload, $content);
	
		// List blobs.
		$listBlobsOptions = new ListBlobsOptions();

		do{
			$result = $blobClient->listBlobs($containerName, $listBlobsOptions);
			foreach ($result->getBlobs() as $blob)
			{
				//echo $blob->getName().": ".$blob->getUrl()."<br />";
				$blobUrl = $blob->getUrl();
			}
		
			$listBlobsOptions->setContinuationToken($result->getContinuationToken());
		} 
		while($result->getContinuationToken());

        // Get blob.
        //echo "This is the content of the blob uploaded: ";
        $blob = $blobClient->getBlob($containerName, $fileToUpload);
        //fpassthru($blob->getContentStream());
        echo "File berhasil diupload, klik button Analyze untuk menganalisa gambar";
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }
    catch(InvalidArgumentTypeException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }
} 
if (isset($_GET["Cleanup"]))  
{
    try{
        // Delete container.
        echo "Deleting Container".PHP_EOL;
        echo $_GET["containerName"].PHP_EOL;
        echo "<br />";
        $blobClient->deleteContainer($_GET["containerName"]);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }
}
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Submission 2 MACD</title>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
	</head>
	<body>
		<h1>Analisa Gambar dengan Azure Computer Vision</h1>
		<p>Pilih gambar yang akan dianalisa.</p>
		<form method="post" action="index.php" enctype="multipart/form-data">
			<input type="file" name="fileToUpload" accept=".jpeg,.jpg,.png" required=""></br></br>
			<input type="submit" name="insert" value="Upload">
		</form>
		<script type="text/javascript">
			function processImage() {
				var subscriptionKey = "SUBSCRIPTIONKEY";
				var uriBase =
					"https://dicodingvisionvell.cognitiveservices.azure.com/vision/v2.0/analyze";
					//"https://southeastasia.api.cognitive.microsoft.com/vision/v2.0/analyze";
					
		 
				// Request parameters.
				var params = {
					"visualFeatures": "Categories,Description,Color",
					"details": "",
					"language": "en",
				};
		 
				// Display the image.
				var sourceImageUrl = document.getElementById("inputImage").value;
				document.querySelector("#sourceImage").src = sourceImageUrl;
		 
				// Make the REST API call.
				$.ajax({
					url: uriBase + "?" + $.param(params),
		 
					// Request headers.
					beforeSend: function(xhrObj){
						xhrObj.setRequestHeader("Content-Type","application/json");
						xhrObj.setRequestHeader(
							"Ocp-Apim-Subscription-Key", subscriptionKey);
					},
		 
					type: "POST",
		 
					// Request body.
					data: '{"url": ' + '"' + sourceImageUrl + '"}',
				})
		 
				.done(function(data) {
					// Show formatted JSON on webpage.
					$("#responseTextArea").val(JSON.stringify(data, null, 2));
				})
		 
				.fail(function(jqXHR, textStatus, errorThrown) {
					// Display error message.
					var errorString = (errorThrown === "") ? "Error. " :
						errorThrown + " (" + jqXHR.status + "): ";
					errorString += (jqXHR.responseText === "") ? "" :
						jQuery.parseJSON(jqXHR.responseText).message;
					alert(errorString);
				});
			};
		</script>
		<br>
		<input type="hidden" name="inputImage" id="inputImage" value="<?php echo $blobUrl ?>"/>
		<button onclick="processImage()" id="analyze">Analyze image</button>
		<script language="javascript">
			document.getElementById('analyze').click(); 
		</script>
		<br><br>
		<div id="wrapper" style="width:1020px; display:table;">
			<div id="jsonOutput" style="width:600px; display:table-cell;">
				Response:
				<br><br>
				<textarea id="responseTextArea" class="UIInput" style="width:580px; height:400px;"></textarea>
			</div>
			<div id="imageDiv" style="width:420px; display:table-cell;">
				Source image:
				<br><br>
				<img id="sourceImage" width="400" />
			</div>
		</div>

	</body>
</html>

