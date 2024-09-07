<?php


class Post
{
	private $error = "";
	public function create_post($userid, $data, $files)
	{
		if(!empty($data['post']) || !empty($files['file']['name']) || isset($data['is_profile_image']) || isset($data['is_cover_image']))
		{

			$myimage = "";
			$has_image = 0;
			$is_cover_image = 0;
			$is_profile_image = 0;

			if(isset($data['is_profile_image']) || isset($data['is_cover_image']))
			{
				 $myimage = $files;
				 $has_image = 1;

				 if(isset($data['is_cover_image']))
				 {
				 	$is_cover_image = 1;

				 }
				 if(isset($data['is_profile_image']))
				 {
				 	$is_profile_image = 1;

				 }

			}

			else
			{

				if(!empty($files['file']['name']))
				{

					$folder = "uploads/" . $userid . "/";

					//create folder
					if(!file_exists($folder))
					{

						mkdir($folder, 0777, true);
						//puting index.php file in each created folder
						file_put_contents($folder . "index.php", "");
					}

					$image_class = new Image();

					$myimage= $folder . $image_class->generate_filename(15) . ".jpg";
					move_uploaded_file($_FILES['file']['tmp_name'], $myimage);

					$image_class->resize_image($myimage, $myimage, 1500, 1500);

					$has_image = 1;

				}
			}
			$post = "";
			
			if(isset($data['post']))
			{

				$post = addslashes($data['post']);
			}

			$postid = $this->create_postid();
			$parent = 0;
			$DB = new Database();
			if(isset($data['parent']) && is_numeric($data['parent']))
			{
				$parent = $data['parent'];

				$sql = "update posts set comments = comments + 1 where postid = '$parent' limit 1";

				$DB->save($sql);
			}

			$query = "insert into posts (userid, postid, post, image, has_image, is_profile_image, is_cover_image, parent) values ('$userid', '$postid', '$post', '$myimage', '$has_image', '$is_profile_image', '$is_cover_image', '$parent')";

			
			$DB->save($query);
		}
		else
		{
			return $this->error .= "Please type somthing to post!!<br>";

		}

	}

	public function edit_post($data, $files)
	{
		if(!empty($data['post']) || !empty($files['file']['name']))
		{

			$myimage = "";
			$has_image = 0;


				if(!empty($files['file']['name']))
				{

					$folder = "uploads/" . $userid . "/";

					//create folder
					if(!file_exists($folder))
					{

						mkdir($folder, 0777, true);
						//puting index.php file in each created folder
						file_put_contents($folder . "index.php", "");
					}

					$image_class = new Image();

					$myimage= $folder . $image_class->generate_filename(15) . ".jpg";
					move_uploaded_file($_FILES['file']['tmp_name'], $myimage);

					$image_class->resize_image($myimage, $myimage, 1500, 1500);

					$has_image = 1;

				}
			$post = "";

			if(isset($data['post']))
			{

				$post = addslashes($data['post']);
			}

			$postid = addslashes($data['postid']);

			if($has_image)
			{
				$query = "update posts set post = '$post', image = '$myimage' where postid = '$postid' limit 1";

			}
			else
			{
				$query = "update posts set post = '$post' where postid = '$postid' limit 1";

			}

			$DB = new Database();
			$DB->save($query);
		}
		else
		{
			return $this->error .= "Please type somthing to post!!<br>";

		}

	}


	public function get_posts($id)
	{
		$query = "select * from posts where parent = 0 and userid = '$id' order by id desc limit 10";

		$DB = new Database();
		$result = $DB->read($query);

		if($result)
		{
			return $result;
		}
		else
		{
			return false;
		}
	}
	public function get_comments($id)
	{

		$query = "select * from posts where parent = '$id' order by id asc limit 30";

		$DB = new Database();
		$result = $DB->read($query);


		if($result)
		{
			return $result;
		}
		else
		{
			return false;
		}
	}

	public function get_one_post($postid)
	{
		if(!is_numeric($postid))
		{
			return false;
		}

		$query = "select * from posts where postid = '$postid' limit 1";

		$DB = new Database();
		$result = $DB->read($query);

		if($result)
		{
			return $result[0];
		}
		else
		{
			return false;
		}
	}

//creating the delete function
	public function delete_post($postid)
	{
		if(!is_numeric($postid))
		{
			return false;
		}
		
		$DB = new Database();
		$sql = "select parent from posts where postid = '$postid' limit 1";
		$result  = $DB->read($sql);

		if(is_array($result))
		{
			if($result[0]['parent'] > 0)
			{
				$parent = $result[0]['parent'];

				$sql = "update posts set comments = comments - 1 where postid = '$parent' limit 1";

				$DB->save($sql);
			}
		}

		$query = "delete from posts where postid = '$postid' limit 1";

		
		$DB->save($query);
	}


	public function i_own_post($postid, $mybook_userid)
	{
		if(!is_numeric($postid))
		{
			return false;
		}

		$query = "select * from posts where postid = '$postid' limit 1";

		$DB = new Database();
		$result = $DB->read($query);

		if(is_array($result))
		{
			if($result[0]['userid'] == $mybook_userid)
			{
				return true;
			}
		}

		return false;
	}

	public function get_likes($id, $type)
	{
		$DB = new Database();
		$type = addslashes($type);
		if(is_numeric($id))
		{

			//get like details
			$sql = "select likes from likes where type = '$type' && contentid = '$id' limit 1";
			$result = $DB->read($sql);

			if(is_array($result))
			{
				$likes = json_decode($result[0]['likes'],true);

				return $likes;
			}
		}

		return false;
	}

	public function like_post($id,$type, $mybook_userid)
	{

			$DB = new Database();

			//save like details
			$sql = "select likes from likes where type = '$type' && contentid = '$id' limit 1";
			$result = $DB->read($sql);
			if(is_array($result))
			{
				$likes = json_decode($result[0]['likes'],true);

					//creating an array column
					$user_ids = array_column($likes, "userid");

					if(!in_array($mybook_userid, $user_ids))
					{

						$arr["userid"] = $mybook_userid;
						$arr["date"] = date("Y-m-d H:i:s");

						$likes[] = $arr;
						
						$likes_string = json_encode($likes);
						$sql = "update likes set likes = '$likes_string' where type = '$type' && contentid = '$id' limit 1";
						$DB->save($sql);

						//increment the right table
						$sql = "update {$type}s set likes = likes + 1 where {$type}id  = '$id' limit 1";
						$DB->save($sql);
					}
					else
					{
						//here we are making sure that if the user has already liked a post he should not like again
						$key = array_search($mybook_userid, $user_ids);
						unset($likes[$key]);

						//rediong encoding.
						$likes_string = json_encode($likes);
						$sql = "update likes set likes = '$likes_string' where type = '$type' && contentid = '$id' limit 1";
						$DB->save($sql);

						//decrement the post tables
						$sql = "update {$type}s set likes = likes - 1 where {$type}id  = '$id' limit 1";
						$DB->save($sql);
					}

				
			}
			else
			{
				$arr["userid"] = $mybook_userid;
				$arr["date"] = date("Y-m-d H:i:s");

				$arr2[] = $arr;

				//we use jason_encode() here to convert our array to a string since our database can only store strings
				$likes = json_encode($arr2);
				$sql = "insert into likes (type,contentid,likes) values ('$type','$id','$likes')";
				$DB->save($sql);

				//increment the right table
				$sql = "update {$type}s set likes = likes + 1 where {$type}id  = '$id' limit 1";
				$DB->save($sql);
			}
	}


	private function create_postid()
	{
		$length = rand(4, 19);
		$number = "";
		for ($i=0; $i < $length; $i++) { 
			// code...
			$new_rand = rand(0, 9);
			$number = $number. $new_rand;
		}

		return $number;
	}
}