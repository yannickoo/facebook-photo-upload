<?php

// We need the facebook php sdk.
require 'facebook-php-sdk/src/facebook.php';
// Just for sexy debugging.
require 'krumo/class.krumo.php';

// Creating the facebook instance
$facebook = new Facebook(array(
  'appId'  => 'Insert your app ID here',
  'secret' => 'Insert your secret token here',
  'cookie' => TRUE,
  'fileUpload' => TRUE,
));

// Here we store the user id of the user.
$user = $facebook->getUser();

?>
<!doctype html>
<html>
<head>
  <title>Upload photo to facebook page</title>
  <style>
    #container {
      width: 600px;
      margin: 10% auto;
    }
  </style>
  <link rel="stylesheet" href="normalize.css">
</head>
<body>
<div id="container">
  <h1>Upload photo to facebook page</h1>
  <?php if(!$user): ?>
  <?php
  // When the user isn't already logged in, we redirect him.
  if($facebook->getUser() == 0) {
    header("Location:{$facebook->getLoginUrl(array('scope' => 'manage_pages,photo_upload'))}");
    exit;
  }
  ?>
<?php else: ?>
  <?php
  // Otherwise we get all the pages he administer.
  $accounts = $facebook->api('/me/accounts');
  // Contains the selected page.
  $page = isset($_POST['page']) && $_POST['page'] ? $_POST['page'] : 0;

  // When the user didn't select a page, we need to prepare a list.
  if(!$page) {
    $pages = array();
    // We literate all available pages.
    foreach($accounts['data'] as $account) {
      // We need the page id and the page access token.
      $pages[$account['id'] . '-' . $account['access_token']] = utf8_decode($account['name']);
    }
  } else {
    // When a page was selected we need to explode the string.
    $page = explode('-', $page);
    // The first part contains the page id.
    $page_id = $page[0];
    // The last part contains the page access token.
    $page_token = $page[1];

    // Now we grab all photo albums of the page.
    $albums = $facebook->api($page_id . '/albums', 'GET');
    $albums = $albums['data'];
    $available_albums = array();

    // We literate all albums.
    foreach($albums as $album) {
      // Storing the album id and the album name.
      $available_albums[$album['id']] = $album['name'];
    }
  }

  // When the photo was uploaded we have it in $_FILES.
  if(isset($_FILES) && !empty($_FILES)) {
    // Now we collect all previously collected data.
    $album_id = isset($_POST['album']) && $_POST['album'] ? $_POST['album'] : '';
    $photo = $_FILES['photo']['tmp_name'];
    $message = $_POST['message'];

    // Preparing arguments for the api call later.
    $args = array(
      'message' => $message,
      'image' => '@' . $photo,
      'access_token' => $page_token,
    );

    // Now we post the photo to the album with the args.
    $photo = $facebook->api($album_id . '/photos', 'post', $args);

    // When we get a photo id we redirect to the posted photo on facbook.
    if(is_array($photo) && $photo['id']) {
      $photo_info = $facebook->api('/' . $photo['id'] . '?fields=link');
      header("Location:{$photo_info['link']}");
      exit();
    }


}

?>
<?php endif; ?>
<?php if(!isset($_POST['page'])): ?>
  <form method="post">
    <div>
      <label for="page">Select page:</label><br>
      <select id="page" name="page">
        <?php
        foreach($pages as $id_token => $name) {
          print '<option value="' . $id_token . '">' . $name . '</option>';
        }
        ?>
      </select>
    </div>
    <div>
      <p><input type="submit" value="Choose" /></p>
    </div>
  </form>
<?php else: ?>
  <form method="post" enctype="multipart/form-data">
    <?php if(count($available_albums)): ?>
    <div>
      <label for="album">Album:</label><br>
      <select id="album" name="album">
        <?php
        foreach($available_albums as $id => $name) {
          print '<option value="' . $id . '">' . $name . '</option>';
        }
        ?>
      </select>
    </div>
  <?php else: ?>
  <div>
    <p>There are no albums available, a new folder will be created.</p>
  </div>
  <?php endif; ?>
    <div>
      <label for="photo">Photo:</label><br>
      <input type="file" id="photo" name="photo" accept="image/gif, image/jpeg, image/png" required />
    </div>
    <div>
      <label for="message">Message:</label><br>
      <input type="text" id="message" name="message" />
    </div>
    <div>
      <input type="submit" value="Upload" />
    </div>
    <input type="hidden" name="page" value="<?php print $_POST['page']; ?>" />
  </form>
<?php endif; ?>
</div>
</body>
</html>
