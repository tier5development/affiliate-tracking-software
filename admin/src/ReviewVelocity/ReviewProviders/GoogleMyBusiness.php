<?php namespace ReviewVelocity\ReviewProviders;
use Vokuro\Models\Location;
use Vokuro\Models\Review;

class GoogleMyBusiness implements IProvider{

    public function __construct(){
        print realpath(__DIR__.'/../../');

    }


    public function setLocation(Location $location)
    {
        // TODO: Implement setLocation() method.
    }

    public function getReviewsByLocation(Location $location)
    {
        // TODO: Implement getReviewsByLocation() method.
    }

    public function getLocationsByBusinessId($business_id)
    {
        // TODO: Implement getLocationsByBusinessId() method.
    }

    public function importReviews()
    {
        // TODO: Implement importReviews() method.
    }

}