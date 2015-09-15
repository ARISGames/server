<?php
require_once('./config.class.php');
if ( isset($_FILES['raw_upload']) ) {
  if ($_FILES['raw_upload']['error']) {
    // do nothing
    // var_dump($_FILES['raw_upload']);
  } else {
    $filename = date('Ymd_His_') . rand(0, 100000);
    $fullpath = Config::raw_uploads_folder . '/' . $filename;
    move_uploaded_file($_FILES['raw_upload']['tmp_name'], $fullpath);
    echo $filename;
  }
} else {
?><!DOCTYPE html>
<html>
<head>
<title>Uploader</title>
<script language="javascript" src="//code.jquery.com/jquery-2.1.4.min.js"></script>
<script language="javascript">
$(document).ready(function(){
  $('#the-button').click(function(){
    var formData = new FormData($('form')[0]);
    $.ajax({
      url: '',
      type: 'POST',
      success: function(e){ console.log(e); },
      data: formData,
      cache: false,
      contentType: false,
      processData: false,
    });
  });
});
</script>
</head>
<body>

<form enctype="multipart/form-data">
  <input type="file" name="raw_upload" id="file-upload" />
  <button type="button" id="the-button">Submit!</button>
</form>

</body>
</html><?php
}
