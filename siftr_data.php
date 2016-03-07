<?php
if ( isset($_GET['game_id']) ) {
?><!DOCTYPE html>
<html>
<head>
<title>Siftr Data</title>
<script language="javascript" src="//code.jquery.com/jquery-2.1.4.min.js"></script>
<script language="javascript">
  $.ajax({
    contentType: 'application/json',
    data: JSON.stringify({"game_id": <?php echo $_GET['game_id']; ?>}),
    dataType: 'json',
    success: function(result){
      var notes = result.data;
      var p, h1, t, img, h3;
      notes.forEach(function(note){
        h1 = document.createElement('h1');
        t = document.createTextNode(note.user.display_name + ' at ' + note.created);
        h1.appendChild(t);
        document.body.appendChild(h1);

        p = document.createElement('p');
        img = document.createElement('img');
        img.src = note.media.data.url;
        p.appendChild(img);
        document.body.appendChild(p);

        p = document.createElement('p');
        t = document.createTextNode(note.description);
        p.appendChild(t);
        document.body.appendChild(p);

        note.comments.data.forEach(function(comment){
          h3 = document.createElement('h3');
          t = document.createTextNode(comment.user.display_name + ' at ' + comment.created);
          h3.appendChild(t);
          document.body.appendChild(h3);

          p = document.createElement('p');
          t = document.createTextNode(comment.description);
          p.appendChild(t);
          document.body.appendChild(p);
        });

        document.body.appendChild( document.createElement('hr') );
      });
    },
    processData: false,
    type: 'POST',
    url: "http://arisgames.org/server/json.php/v2.notes.allSiftrData",
  });
</script>
</head>
<body>

</body>
</html><?php
} else {
?><!DOCTYPE html>
<html>
<head>
<title>Siftr Data</title>
</head>
<body>

<form method="get">
  <input name="game_id" type="text" placeholder="Game ID"></input>
  <button type="submit">Load Data</button>
</form>

</body>
</html><?php
}
