<?php

require_once 'starter.php';

define('IMG_PATH', 'https://odemmer.000webhostapp.com/assets/img/');

$response = array();
$results = null;
$page_num = 1;
$start = 0;
$limit = 12;

if (isset($_GET['apicall'])) {

    if (isset($_GET['page_num'])) {
        $page_num = $_GET['page_num'];
    }

    switch ($_GET['apicall']) {

        case 'fetch_ads':

            //    fetching product based on id 
            if (isset($_GET['cat_id'])) {
                $cat_id = $_GET['cat_id'];
                $stmt = $connection->prepare("SELECT products.prod_id,products.name,products.prod_id,products.img_path
                FROM products
                INNER JOIN product_category ON
                product_category.prod_cat_ID=products.prod_cat_id
                where product_category.prod_cat_ID = '$cat_id'
                ORDER BY RAND() 
                limit 6");
            } else {
                // getting ads
                $page = $_GET['page'];

                //gettotal
                $stmt = $connection->prepare("SELECT ads_id FROM ads_spacing");
                $stmt->execute();
                $stmt->store_result();
                $stmt->fetch();

                $total =  $stmt->num_rows;
                $limit = ceil($total / 2);
                $start = ($page - 1) * $limit;
                $stmt->close();

                $stmt = $connection->prepare("SELECT ads_spacing.ads_id,ads_spacing.header,ads_spacing.prod_id,products.img_path
                FROM ads_spacing
                INNER JOIN products ON
                ads_spacing.prod_id=products.prod_id
                limit $start, $limit");
            }

            $stmt->execute();
            $stmt->bind_result($ads_id, $header, $prod_id, $img_path);
            $results = array();

            while ($stmt->fetch()) {
                $temp = array();
                $temp['ads_id'] = $ads_id;
                $temp['header'] = $header;
                $temp['prod_id'] = $prod_id;
                $temp['img_path'] = IMG_PATH . $img_path;
                array_push($results, $temp);
            }
            $response['results'] = $results;
            break;

        case 'fetch_products':
            $src = $_GET['src'];

            switch ($src) {
                case "recommendation":
                    //getting recommendation
                    $stmt = $connection->prepare("SELECT prod_id,prod_cat_id,quantity,price,name,img_path,description,status,timestamp
                    FROM products
                    ORDER BY RAND() 
                    limit $start, $limit");
                    break;
                case "new_arrival":
                    //getting New arrivals
                    $stmt = $connection->prepare("SELECT prod_id,prod_cat_id,quantity,price,name,img_path,description,status,timestamp
                    FROM products
                    ORDER BY timestamp DESC
                    limit $start, $limit");
                    break;
                case "all_products":
                    //getting New arrivals

                    $page = $_GET['page'];
                    $start = ($page - 1) * $limit;

                    $stmt = $connection->prepare("SELECT prod_id,prod_cat_id,quantity,price,name,img_path,description,status,timestamp
                    FROM products
                    ORDER BY RAND() 
                    limit $start, $limit");
                    break;
                case "cat_prod":
                    //getting items based on category
                    $cat_id = $_GET['cat_id'];
                    $order = null;

                    //sorting
                    if (isset($_GET['sort'])) {
                        $sort_data = $_GET['sort'];
                        switch ($sort_data) {
                            case "timestamp":
                                $order = "ORDER BY products.$sort_data DESC";
                                break;
                            case "price":
                                $order = "ORDER BY products.$sort_data DESC";
                                break;
                            case "rating":
                                $order = "ORDER BY (SELECT COUNT(*) FROM product_reviews ct WHERE ct.prod_id =  products.prod_id) 
                                DESC";
                                break;
                            case "most_popular":
                                $order = "ORDER BY (SELECT COUNT(*) FROM user_purchases ct WHERE ct.prod_id =  products.prod_id) 
                                DESC";
                                break;
                        }
                    } else {
                        //default sort
                        $order = "ORDER BY products.timestamp DESC";
                    }

                    $stmt = $connection->prepare("SELECT products.prod_id,products.prod_cat_id,products.quantity,
                    products.price,products.name,products.img_path,products.description,products.status,products.timestamp
                    FROM products
                    INNER JOIN product_category ON
                    product_category.prod_cat_ID=products.prod_cat_id
                    where product_category.prod_cat_ID = '$cat_id'
                    $order");

                    break;
                case "user_purchase":
                    //getting users orders
                    $user_id = $_GET['user_id'];
                    $stmt = $connection->prepare("SELECT products.prod_id,products.prod_cat_id,products.quantity,
                    user_purchases.total,products.name,products.img_path,products.description,user_purchases.status,user_purchases.timestamp
                    FROM products
                    INNER JOIN user_purchases ON
                    user_purchases.prod_id=products.prod_id
                    where user_purchases.user_id = '$user_id'
                    ORDER BY user_purchases.timestamp DESC");
                    break;
                case "subProducts":
                    //getting products based on sub category
                    $SubCategoryId  = $_GET['SubCategoryId'];
                    $stmt = $connection->prepare("SELECT prod_id,prod_cat_id,quantity,price,name,img_path,description,status,timestamp
                    FROM products
                    INNER JOIN product_category_sub ON
                    products.sub_cat_id=product_category_sub.SubCategoryId
                    where product_category_sub.SubCategoryId = '$SubCategoryId'
                    ORDER BY products.prod_id  DESC");
                    break;
                case "wish_list":
                    //getting users wishlist
                    $user_id = $_GET['user_id'];
                    $stmt = $connection->prepare("SELECT products.prod_id,products.prod_cat_id,products.quantity,
                    products.price,products.name,products.img_path,products.description,products.status,products.timestamp
                    FROM products
                    INNER JOIN user_wishlist ON
                    user_wishlist.prod_id=products.prod_id
                    where user_wishlist.user_id = '$user_id'
                    ORDER BY products.timestamp DESC");
                    break;
                case "search_term":
                    //search handler
                    $search_term = $_GET['term'];
                    $page = $_GET['page'];
                    $start = ($page - 1) * $limit;

                    // search filter
                    $search = explode(" ", $search_term);
                    $search_string = "";
                    foreach ($search as $word) {
                        $search_string .= metaphone($word) . " ";
                    }

                    $stmt = $connection->prepare("SELECT prod_id,prod_cat_id,quantity,price,name,img_path,description,status,timestamp
                    FROM products
                    where indexing like '%$search_string%'
                    ORDER BY timestamp 
                    limit $start, $limit");
                    break;
            }

            $stmt->execute();
            $stmt->bind_result($prod_id, $prod_cat_id, $quantity, $price, $name, $img_path, $description, $status, $timestamp);
            $results = array();

            while ($stmt->fetch()) {
                $temp = array();
                $temp['prod_id'] = $prod_id;
                $temp['prod_cat_id'] = $prod_cat_id;
                $temp['quantity'] = $quantity;
                $temp['price'] =  $price;
                $temp['name'] = $name;
                $temp['img_path'] = IMG_PATH . $img_path;
                $temp['description'] = $description;
                $temp['status'] = $status;
                $temp['timestamp'] = $timestamp;
                $temp['rating'] = $price;
                array_push($results, $temp);
            }
            $response['results'] = $results;
            break;

        case 'fetchUserOrders':
            $user_id = $_GET['user_id'];
            $src = $_GET['src'];

            $results = array();

            switch ($src) {
                case "getTransactions":
                    $query = "SELECT trans_id, status,timestamp,total,address
                    from user_purchases
                    where user_purchases.user_id = $user_id
                    Group By trans_id
                    order by trans_id DESC
                    ";
                    $ret = mysqli_query($connection, $query);

                    while ($row = mysqli_fetch_array($ret)) {
                        $trans_id = $row['trans_id'];
                        $status = $row['status'];
                        $timestamp = $row['timestamp'];
                        $total = $row['total'];
                        $address = $row['address'];

                        $temp = array();
                        $temp['trans_id'] = $trans_id;
                        $temp['status'] = $status;
                        $temp['timestamp'] = $timestamp;
                        $temp['total'] = $total;
                        $temp['address'] = $address;
                        $temp['user_id'] = $user_id;

                        array_push($results, $temp);
                    }
                    break;
                case "getOrders":
                    $trans_id = $_GET['trans_id'];
                    $stmt = $connection->prepare("SELECT products.prod_id,products.prod_cat_id,products.quantity,
                    products.price,products.name,products.img_path,products.description,products.status,products.timestamp
                    FROM products
                    INNER JOIN user_purchases ON
                    user_purchases.prod_id=products.prod_id
                    where user_purchases.trans_id = '$trans_id' and user_purchases.user_id = $user_id
                    ORDER BY user_purchases.timestamp DESC");
                    $stmt->execute();
                    $stmt->store_result();
                    $stmt->bind_result($prod_id, $prod_cat_id, $quantity, $price, $name, $img_path, $description, $status, $timestamp);

                    while ($stmt->fetch()) {
                        $temp = array();
                        $temp['prod_id'] = $prod_id;
                        $temp['prod_cat_id'] = $prod_cat_id;
                        $temp['quantity'] = $quantity;
                        $temp['price'] =  $price;
                        $temp['name'] = $name;
                        $temp['img_path'] = IMG_PATH . $img_path;
                        $temp['description'] = $description;
                        $temp['status'] = $status;
                        $temp['timestamp'] = $timestamp;
                        $temp['rating'] = $price;
                        array_push($results, $temp);
                    }

                    break;
            }


            $response['results'] = $results;
            break;

        case 'fetch_category':

            //gettotal
            $stmt = $connection->prepare("SELECT prod_cat_ID FROM product_category");
            $stmt->execute();
            $stmt->store_result();
            $stmt->fetch();

            $total =  $stmt->num_rows;
            $limit = ceil($total / 2);
            if ($limit > 6) {
                $limit = 6;
            }
            $stmt->close();

            $stmt = $connection->prepare("SELECT prod_cat_ID,name,img_path
            FROM product_category
            ORDER BY RAND()");

            $stmt->execute();
            $stmt->bind_result($prod_cat_id, $name, $img_path);
            $results = array();

            while ($stmt->fetch()) {
                $temp = array();
                $temp['prod_cat_id'] = $prod_cat_id;
                $temp['name'] = $name;
                $temp['img_path'] = IMG_PATH . $img_path;
                array_push($results, $temp);
            }
            $response['results'] = $results;
            break;

            // Fetch sub category based on CategoryID
        case 'fetchCategorySub':
            $CategoryId = $_GET['Id'];

            $stmt = $connection->prepare("SELECT * 
            from product_category_sub   
            Where CategoryId = $CategoryId");

            $stmt->execute();
            $stmt->bind_result($SubCategoryId, $CategoryId, $Title, $ImgPath);
            $results = array();

            while ($stmt->fetch()) {
                $temp = array();
                $temp['SubCategoryId'] = $SubCategoryId;
                $temp['CategoryId'] = $CategoryId;
                $temp['Title'] = $Title;
                $temp['ImgPath'] = IMG_PATH . $ImgPath;
                array_push($results, $temp);
            }
            $response['results'] = $results;
            break;

        case 'fetchAllSUb':
            $stmt = $connection->prepare("SELECT * 
                from product_category_sub
                ");

            $stmt->execute();
            $stmt->bind_result($SubCategoryId, $CategoryId, $Title, $ImgPath);
            $results = array();

            while ($stmt->fetch()) {
                $temp = array();
                $temp['SubCategoryId'] = $SubCategoryId;
                $temp['CategoryId'] = $CategoryId;
                $temp['Title'] = $Title;
                $temp['ImgPath'] = IMG_PATH . $ImgPath;
                array_push($results, $temp);
            }
            $response['results'] = $results;
            break;

        case 'indexSub':
            //check if account exist
            $stmt = $connection->prepare("UPDATE products SET sub_cat_id = 4");
            $stmt->execute();

            $stmt->close();
            break;

        case 'fetch_product_item':
            $product_id = $_GET['product_id'];

            $stmt = $connection->prepare("SELECT prod_cat_id,name,img_path,description,price,quantity
            FROM products
            Where prod_id = '$product_id'");
            $stmt->execute();
            $stmt->bind_result($prod_cat_id, $name, $img_path, $description, $price, $quantity);
            $results = array();

            while ($stmt->fetch()) {
                $temp = array();
                $temp['prod_cat_id'] = $prod_cat_id;
                $temp['name'] = $name;
                $temp['img_path'] = IMG_PATH . $img_path;
                $temp['description'] = $description;
                $temp['price'] = $price;
                $temp['quantity'] = $quantity;
                array_push($results, $temp);
            }
            $response['results'] = $results;
            break;

        case 'account_handler':
            $src = $_GET['src'];
            $email = $_POST['email'];

            switch ($src) {
                case "register":
                    $names = $_POST['names'];
                    $password = md5($_POST['password']);

                    //check if account exist
                    $stmt = $connection->prepare("SELECT user_id,names,email,address,img_path,acc_type FROM users WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->store_result();
                    if ($stmt->num_rows == 0) {
                        // if doesnt exist
                        $stmt = $connection->prepare("INSERT INTO users (names,email,password,acc_type)
                        VALUES ('$names','$email','$password','ordinary')");
                        if (!$stmt->execute()) {
                            $response['message'] = $connection->errno . ' ' . $connection->error;
                        }
                    }
                    $stmt->close();
                    break;

                    //updating user information
                case "update_user":
                    $name = $_GET['name'];
                    $address = $_GET['address'];
                    $contact = $_GET['contact'];

                    $stmt = $connection->prepare("UPDATE users SET names = ?,address = ?,contact = ? WHERE email = ?");
                    $stmt->bind_param("ssss", $name, $address, $contact, $email);
                    $stmt->execute();

                    $stmt->close();
                    break;
            }

            //fetching account details if registered
            $stmt = $connection->prepare("SELECT user_id,names,email,address,img_path,acc_type,contact FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 0) {
                $response['error'] = true;
                $response['message'] = 'Account Does Not Exist';
                $stmt->close();
            } else {
                $stmt->bind_result($user_id, $names, $email, $address, $img_path, $acc_type, $contact);
                $results = array();

                while ($stmt->fetch()) {
                    $temp = array();
                    $temp['user_id'] = $user_id;
                    $temp['names'] = $names;
                    $temp['email'] = $email;
                    $temp['contact'] = $contact;
                    $temp['address'] = $address;
                    $temp['acc_type'] = $acc_type;
                    $temp['img_path'] = IMG_PATH . $img_path;
                    array_push($results, $temp);
                }
                $response['error'] = false;
                $response['message'] = 'Login Successful';
                $response['results'] = $results;
            }

            break;

        case 'update_purchase':
            $user_id = $_POST['user_id'];
            $total = $_POST['total'];
            $address = $_POST['address'];
            $dist_id = $_POST['dist_id'];
            $array_size = $_GET['array_size'];
            $trans_id = $_POST['trans_id'];

            $user_name = $_POST['user_name'];
            $user_phone = $_POST['user_phone'];
            $email = $_POST['email'];
            $user_info = "name: " . $user_name . "\nphone#: " . $user_phone . "\nemail: " . $email;

            //check if user is signed in
            if (isset($_GET['notSigned'])) {
                $stmt = $connection->prepare("SELECT user_id 
                FROM users
                Where names = 'guestvm2020'");

                $stmt->execute();
                $stmt->bind_result($user_ids);
                $results = array();
                while ($stmt->fetch()) {
                    $user_id = $user_ids;
                }
                $stmt->close();
            }


            //loop through each item
            for ($i = 0; $i < $array_size; $i++) {

                $prod_id = $_POST["productId($i)"];

                $stmt = $connection->prepare("INSERT INTO user_purchases (user_id,prod_id,total,status,address,dist_id,trans_id,user_info) 
                    VALUES ($user_id,$prod_id,$total,'pending','$address',$dist_id,'$trans_id','$user_info')");
                if (!$stmt->execute()) {
                    $response['response'] = $connection->errno . ' ' . $connection->error;
                    $i = $array_size + 5;
                } else {
                    $response['response'] = "Purchase Successful";
                }
            }
            break;

        case 'handle_wish_list':
            $src = $_GET['src'];
            $user_id = $_GET['user_id'];
            $prod_id = $_GET['prod_id'];

            switch ($src) {
                case "add_wish":
                    //check if exist
                    $stmt = $connection->prepare("SELECT user_id FROM user_wishlist WHERE user_id = '$user_id' and prod_id = '$prod_id'");
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows > 0) {
                        $response['message'] = 'Already in wishlist';
                        $stmt->close();
                    } else {
                        $stmt = $connection->prepare("INSERT INTO user_wishlist (user_id,prod_id) 
                        VALUES ('$user_id','$prod_id')");

                        if (!$stmt->execute()) {
                            $response['message'] = $connection->errno . ' ' . $connection->error;
                        } else {
                            $response['response'] = "product added to wish list";
                        }
                    }
                    break;

                case "remove_wish":
                    //getting New arrivals
                    $stmt = $connection->prepare("DELETE FROM user_wishlist WHERE user_id = '$user_id' and prod_id = '$prod_id'");
                    if (!$stmt->execute()) {
                        $response['message'] = $connection->errno . ' ' . $connection->error;
                    } else {
                        $response['response'] = "product removed from wish list";
                    }
                    break;
            }

            break;

        case 'product_rating':
            $prod_id = $_GET['prod_id'];
            $src = $_GET['src'];
            $total_user_votes = 0;
            $star_rating = 0;


            switch ($src) {
                case "add_review":
                    $rate_val = $_GET['rate_val'];
                    $text_val = $_GET['text_val'];
                    $user_id = $_GET['user_id'];

                    $stmt = $connection->prepare("INSERT INTO product_reviews (user_id,prod_id,text,rating) 
                    VALUES ($user_id,$prod_id,'$text_val',$rate_val)");
                    $app_response = "Review Uploaded";
                    break;
                case "delete_review":
                    $review_id = $_GET['review_id'];
                    $stmt = $connection->prepare("DELETE FROM product_reviews WHERE user_id = '$user_id' and review_id = '$review_id'");
                    $app_response = "Review Deleted";
                case "fetch_review":
                    $prod_id = $_GET['prod_id'];

                    //fetching averages
                    $stmt = $connection->prepare("SELECT review_id FROM product_reviews WHERE prod_id = '$prod_id'");
                    $stmt->execute();
                    $stmt->store_result();
                    $total_user_votes = $stmt->num_rows;
                    $stmt->close();

                    $stmt = $connection->prepare("SELECT product_reviews.review_id,product_reviews.text,
                    product_reviews.rating,product_reviews.timestamp,users.img_path,users.names
                    FROM product_reviews
                    INNER JOIN users ON
                    product_reviews.user_id=users.user_id
                    where product_reviews.prod_id = '$prod_id'");
                    $stmt->execute();
                    break;
            }

            if ($src == "fetch_review") {

                $stmt->store_result();
                $stmt->bind_result($review_id, $text, $rating, $timestamp, $img_path, $user_name);
                $results = array();

                while ($stmt->fetch()) {
                    $temp = array();
                    $temp['review_id'] = $review_id;
                    $temp['rating'] = $rating;
                    $temp['user_name'] = $user_name;
                    $temp['img_path'] = IMG_PATH . $img_path;
                    $temp['review_date'] = $timestamp;
                    $temp['review_txt'] = $text;
                    array_push($results, $temp);

                    $star_rating =  $star_rating + $rating;
                }
                $response['results'] = $results;
                $response['star_rating'] = $star_rating;
                $response['total_user_votes'] = $total_user_votes;
                $response['vote_average'] = $star_rating / $total_user_votes;
            } else {
                if (!$stmt->execute()) {
                    $response['message'] = $connection->errno . ' ' . $connection->error;
                } else {
                    $response['response'] = $app_response;
                }
            }

            break;



            //end
    }
} else {
    //if it is not api call 
    //pushing appropriate values to response array 
    $response['error'] = true;
    $response['message'] = 'Invalid API Call';
}

//displaying the response in json structure 
echo json_encode($response, JSON_UNESCAPED_SLASHES);

function isTheseParametersAvailable($params)
{

    //traversing through all the parameters 
    foreach ($params as $param) {
        //if the paramter is not available
        if (!isset($_POST[$param])) {
            //return false 
            return false;
        }
    }
    //return true if every param is available 
    return true;
}

function getFileExtension($file)
{
    $path_parts = pathinfo($file);
    return $path_parts['extension'];
}

// Compress image
function compressImage($source, $destination, $quality)
{

    $info = getimagesize($source);

    if ($info['mime'] == 'image/jpeg')
        $image = imagecreatefromjpeg($source);

    elseif ($info['mime'] == 'image/gif')
        $image = imagecreatefromgif($source);

    elseif ($info['mime'] == 'image/png')
        $image = imagecreatefrompng($source);

    imagejpeg($image, $destination, $quality);
}

//sanitize
function sanitize($z)
{
    $z = strtolower($z);
    $z = preg_replace('/[^a-z0-9 -]+/', '', $z);
    $z = str_replace(' ', ' ', $z);
    return trim($z, '-');
}
