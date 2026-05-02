<?php

class Service
{
    private $service_id;
    private $title;
    private $category;
    private $subcategory;
    private $price;
    private $delivery_time;
    private $revisions_included;
    private $freelancer_id;
    private $freelancer_name;
    private $main_image_path;
    private $added_to_cart_timestamp;

    public function __construct($service_id, $title, $category, $subcategory, $price, $delivery_time, $revisions_included, $freelancer_id, $freelancer_name, $main_image_path, $added_to_cart_timestamp)
    {
        $this->service_id = (int) $service_id;
        $this->title = $title;
        $this->category = $category;
        $this->subcategory = $subcategory;
        $this->price = (float) $price;
        $this->delivery_time = (int) $delivery_time;
        $this->revisions_included = (int) $revisions_included;
        $this->freelancer_id = (int) $freelancer_id;
        $this->freelancer_name = $freelancer_name;
        $this->main_image_path = $main_image_path;
        $this->added_to_cart_timestamp = $added_to_cart_timestamp;
    }

    public function getServiceId()
    {
        return $this->service_id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function getSubcategory()
    {
        return $this->subcategory;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getFormattedPrice()
    {
        return "$" . number_format($this->price, 2);
    }

    public function getDeliveryTime()
    {
        return $this->delivery_time;
    }

    public function getFormattedDelivery()
    {
        return $this->delivery_time . " days";
    }

    public function getRevisionsIncluded()
    {
        return $this->revisions_included;
    }

    public function getFreelancerId()
    {
        return $this->freelancer_id;
    }

    public function getFreelancerName()
    {
        return $this->freelancer_name;
    }

    public function getMainImagePath()
    {
        return $this->main_image_path;
    }

    public function getAddedToCartTimestamp()
    {
        return $this->added_to_cart_timestamp;
    }

    public function calculateServiceFee()
    {
        return $this->price * 0.05;
    }

    public function getTotalWithFee()
    {
        return $this->price + $this->calculateServiceFee();
    }
}
?>