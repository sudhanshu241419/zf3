<?php
use User\Model\User;
use User\Model\UserMenuReview;
use User\Model\UserReviewImage;
use MCommons\StaticOptions;
use MCommons\S3Lib;

/**
 * Types : profile, menu_review, user_review, restaurant_images
 *
 * @author krunal
 *        
 */
class UploadS3
{

    private $application_env;

    private $_users_model;

    private $_user_menu_review_model;

    private $_user_review_images_model;

    private $_restaurant_images;

    private $_s3;

    private $_type;

    private $_config;

    function __construct()
    {
        $this->_s3 = new S3Lib();
        
        $this->application_env = defined('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local';
        // initialization of models
        $this->_users_model = new User();
        $this->_user_menu_review_model = new UserMenuReview();
        $this->_user_review_images_model = new UserReviewImage();
        // $this->_restaurant_images = new RestaurantImage();
        $sl = StaticOptions::getServiceLocator();
        $this->_config = $sl->get('Config');
        
        // create bucket if it dose not exists.
        if (! $this->_s3->checkBucketExists($this->_config['s3']['bucket_name'])) {
            $this->_s3->createBucket($this->_config['s3']['bucket_name']);
        }
    }

    public function setUp()
    {
        // ... Set up environment for this job
    }

    public function perform()
    {
        // amazon s3 upload oprtation
        try {
            $status = false;
            if (isset($this->args['type'])) {
                $this->_type = $this->args['type'];
                switch ($this->_type) {
                    case 'profile':
                        $status = $this->_uploadUserProfileImage($this->args);
                        break;
                    case 'menu_review':
                        $status = $this->_uploadUserMenuReviewImages();
                        break;
                    case 'user_review':
                        $status = $this->_uploadUserReviewImages();
                        break;
                    case 'restaurant_images':
                        $status = $this->_uploadRestaurantReviewImages();
                        break;
                    default:
                        throw new \Exception('Invalid type specified', 400);
                }
                if (! $status) {
                    throw new \Exception($this->_type . " Upload operation failed.", 400);
                }
            } else {
                throw new \Exception("Image type has not been specified", 400);
            }
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage(), 400);
        }
    }

    private function _uploadUserProfileImage($args)
    {
        $image_url = $this->_config['image_base_urls']['local-api'];
        $bucket_name = $this->_config['s3']['bucket_name'];
        $images = array();
        if ($args['op_type'] == 'all') {
            $images = $this->_users_model->getDpImagesForUpload();
        } else 
            if ($args['op_type'] == 'one') {
                $images = $this->_users_model->getDpImagesForUpload($args['user_id']);
            }
        if (! empty($images)) {
            foreach ($images as $image) {
                $status = false;
                if (isset($image['display_pic_url']) && ! empty($image['display_pic_url']) && $image['display_pic_url'] != null) {
                    $original_image = $image_url . "/user_images/profile/" . $image['id'] . "/" . $image['display_pic_url'];
                    $result = $this->_s3->createObject($bucket_name, "/user_images/profile/" . $image['id'] . "/" . $image['display_pic_url'], $original_image);
                    if ($result) {
                        $status = true;
                    }
                }
                if (isset($image['display_pic_url_normal']) && ! empty($image['display_pic_url_normal']) && $image['display_pic_url_normal'] != null) {
                    $normal_image = $image_url . "/user_images/profile/" . $image['id'] . "/" . $image['display_pic_url_normal'];
                    $result = $this->_s3->createObject($bucket_name, "/user_images/profile/" . $image['id'] . "/" . $image['display_pic_url_normal'], $original_image);
                    if ($result) {
                        $status = true;
                    }
                }
                if (isset($image['display_pic_url_large']) && ! empty($image['display_pic_url_large']) && $image['display_pic_url_large'] != null) {
                    $large_image = $image_url . "/user_images/profile/" . $image['id'] . "/" . $image['display_pic_url_large'];
                    $result = $this->_s3->createObject($bucket_name, "/user_images/profile/" . $image['id'] . "/" . $image['display_pic_url_large'], $original_image);
                    if ($result) {
                        $status = true;
                    }
                }
                if ($status) {
                    try {
                        $this->_users_model->updateImageStatus($image['id']);
                    } catch (\Exception $ex) {}
                }
            }
            return true;
        } else {
            return false;
        }
    }

    private function _uploadUserReviewImages()
    {
        $image_url = $this->_config['image_base_urls']['local-api'];
        $bucket_name = $this->_config['s3']['bucket_name'];
        $images = array();
        try {
            $images = $this->_user_review_images_model->getImagesForUpload();
            if (! empty($images)) {
                foreach ($images as $image) {
                    $status = false;
                    if (isset($image['image_name']) && ! empty($image['image_name']) && $image['image_name'] != null) {
                        $original_image = $image_url . "/user_images/reviews/" . $image['restaurant_id'] . "/" . $image['image_name'];
                        $result = $this->_s3->createObject($bucket_name, "/user_images/reviews/" . $image['restaurant_id'] . "/" . $image['image_name'], $original_image);
                        if ($result) {
                            $status = true;
                        }
                    }
                    if ($status) {
                        try {
                            $this->_user_review_images_model->updateImageStatus($image['id']);
                        } catch (\Exception $ex) {}
                    }
                }
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            return false;
        }
    }

    private function _uploadUserMenuReviewImages()
    {
        $image_url = $this->_config['image_base_urls']['local-api'];
        $bucket_name = $this->_config['s3']['bucket_name'];
        $images = array();
        try {
            $images = $this->_user_menu_review_model->getImagesForUpload();
            if (! empty($images)) {
                foreach ($images as $image) {
                    $status = false;
                    if (isset($image['image_name']) && ! empty($image['image_name']) && $image['image_name'] != null) {
                        $original_image = $image_url . "/user_images/reviews/" . $image['restaurant_id'] . "/menu/" . $image['image_name'];
                        $result = $this->_s3->createObject($bucket_name, "/user_images/reviews/" . $image['restaurant_id'] . "/menu/" . $image['image_name'], $original_image);
                        if ($result) {
                            $status = true;
                        }
                    }
                    if ($status) {
                        try {
                            $this->_user_menu_review_model->updateImageStatus($image['id']);
                        } catch (\Exception $ex) {}
                    }
                }
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            return false;
        }
    }

    private function _uploadRestaurantImages()
    {
        $image_url = $this->_config['image_base_urls']['local-api'];
        $bucket_name = $this->_config['s3']['bucket_name'];
        $images = array();
        try {
            $images = $this->_user_menu_review_model->getImagesForUpload();
            if (! empty($images)) {
                foreach ($images as $image) {
                    $status = false;
                    if (isset($image['image_name']) && ! empty($image['image_name']) && $image['image_name'] != null) {
                        $original_image = $image_url . "/user_images/reviews/" . $image['restaurant_id'] . "/menu/" . $image['image_name'];
                        $result = $this->_s3->createObject($bucket_name, "/user_images/reviews/" . $image['restaurant_id'] . "/menu/" . $image['image_name'], $original_image);
                        if ($result) {
                            $status = true;
                        }
                    }
                    if ($status) {
                        try {
                            $this->_user_menu_review_model->updateImageStatus($image['id']);
                        } catch (\Exception $ex) {}
                    }
                }
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            return false;
        }
        return true;
    }

    public function tearDown()
    {
        // ... Remove environment for this job
    }
}