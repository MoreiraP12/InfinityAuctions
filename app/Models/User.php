<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Log;

class User extends Authenticatable
{
    use Notifiable;

    public $timestamps = false;

    public function getEmailForPasswordReset()
    {
        return $this->email;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'gender', 'cellphone', 'email', 'birth_date', 'address', 'password', 'credits', 'is_admin', 'profile_image'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function bids()
    {
        return $this->hasMany(Bid::class)->orderBy('amount', 'Desc');
    }

    public function wishlist()
    {
        return $this->belongsToMany(Wishlist::class, 'users_wishlist', 'users_id');
    }

    public function followingAuctions()
    {
        return $this->belongsToMany(Auction::class, 'following');
    }

    public function ownedToBeStartedAuctions()
    {
        return $this->ownedAuctions()->where('state', '=', 'To be started');
    }

    public function ownedRunningAuctions()
    {
        return $this->ownedAuctions()->where('state', '=', 'Running');
    }

    public function ownedAuctions()
    {
        return $this->hasMany(Auction::Class, 'auction_owner_id');
    }

    public function biddingAuctions($user_id)
    {
        return DB::table('auction')
            ->join('bid', 'bid.auction_id', '=', 'auction.id')
            ->where('bid.user_id', '=', $user_id)
            ->select('auction.*')
            ->distinct()
            ->get();
    }

    public function wonAuctions()
    {
        $array = [];
        $bidding = $this->biddingAuctions($this->id);
        foreach($bidding as $auction) {
            $auction_model = Auction::find($auction->id);
            $maxAmount = Bid::all_bids($auction->id)[0]->amount;
            $winnerId = $auction_model->bids()->where('amount', $maxAmount)->value('user_id');
            if ($this->id == $winnerId) {
                $array[] = $auction_model;
            }
        }
        return $array;
    }

    public function reportsMade()
    {
        return $this->hasMany(Report::class, 'reporter');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function wasReported()
    {
        return $this->hasMany(Report::class, 'reported_user');
    }

    public function pendingAucReports() {
        return $this->reportsHandled()
                ->join('auction', 'auction.id', '=', 'report.auction_reported')
                ->whereNotNull('report.auction_reported')
                ->whereNull('report.penalty')
                ->selectRaw("report.id as id, report.reporter as reporter, 
                            report.reported_user as reported_user, report.auction_reported as auction_reported,
                            users.name as user_name, auction.name as auction_name,
                            report.date as date, ARRAY_AGG(report_option.name) as reasons")
                ->groupBy('users.id')
                ->groupBy('auction.id')
                ->groupBy('report.id');
    }

    public function pendingUsrReports() {
        return $this->reportsHandled()
                ->whereNull('report.auction_reported')
                ->whereNull('report.penalty')
                ->selectRaw("report.id as id, report.reporter as reporter, 
                            report.reported_user as reported_user, 
                            users.name as user_name,
                            report.date as date, ARRAY_AGG(report_option.name) as reasons")
                ->groupBy('users.id')
                ->groupBy('report.id');
    }

    public function reportsHandled()
    {
        return $this->hasMany(Report::class, 'admin_id')
                ->join('report_reasons', 'report.id', '=', 'report_reasons.id_report')
                ->join('report_option', 'report_reasons.id_report_option', '=', 'report_option.id')
                ->join('users', 'users.id', '=', 'report.reported_user');
    }

    public function profileImage()
    {
        return $this->belongsTo(Image::class, 'profile_image');
    }

    public function isBanned()
    {
        $reports = $this->wasReported()->get();
        foreach ($reports as $report) {
            $date = date_create($report->date);

            if ($report->penalty == '3 day ban') {
                date_add($date, date_interval_create_from_date_string("3 days")); 
                if ($date >= date_create('now')) return true;
            }
            else if ($report->penalty == '5 day ban') {
                date_add($date, date_interval_create_from_date_string("5 days")); 
                if ($date >= date_create('now')) return true;
            }
            else if ($report->penalty == '10 day ban') {
                date_add($date, date_interval_create_from_date_string("10 days")); 
                if ($date >= date_create('now')) return true;
            }
            else if ($report->penalty == '1 month ban') {
                date_add($date, date_interval_create_from_date_string("1 months")); 
                if ($date >= date_create('now')) return true;
            }
        }
        return false;
    }

    // retorna todos os users que avaliaram o user atual
    public function rate_bidders()
    {
        return $this->belongsToMany(User::class, 'rates', 'id_seller', 'id_bidder');
    }

    // retorna todos os users avaliados pelo current user
    public function rate_sellers()
    {
        return $this->belongsToMany(User::class, 'rates', 'id_bidder', 'id_seller');
    }

    public function getRatingDetails()
    {
        return ["rate" => round($this->rate_bidders()->average('rate'), 2), "numberOfRatings" => $this->rate_bidders()->count()];
    }

    //Users com auctions -> sort desc por rating -> Top 5
    public static function getTopSellers(){
        return DB::select(DB::raw('SELECT users.*, image.path, rate FROM users, (SELECT id_seller, avg(rate) as rate FROM rates group by id_seller) as avgRates, auction, image where users.id=auction.auction_owner_id AND image.id = profile_image AND users.id = id_seller ORDER BY rate DESC LIMIT 5;'));
    }

    public function hasPendingMaxBids()
    {
        return DB::select(DB::raw('select has_max_bid(' . Auth::id() . ');'))[0];
    }

    public static function getUsersWithImages()
    {
        return DB::table('users')
            ->join('image', 'users.profile_image', '=', 'image.id')
            ->select('users.*', 'image.path');
    }

    public static function addBalance($id, $amount)
    {
        $user = User::find($id);
        $user->credits = User::getBalance($id) + $amount;
        return $user->save();
    }

    public static function removeBalance($id, $amount)
    {
        $user = User::find($id);
        $user->credits = User::getBalance($id) - $amount;
        return $user->save();
    }

    public static function getBalance($id)
    {
        return User::find($id)->credits;
    }

    public static function heldBalance($user_id)
    {
        $value = DB::select(DB::raw('SELECT SUM(max)
                                    FROM (  SELECT BID.auction_id, BID.user_id, MAX(BID.amount)
                                            FROM BID
                                            INNER JOIN AUCTION ON BID.auction_id=AUCTION.id
                                            WHERE AUCTION.state = \'Running\'
                                            GROUP BY auction_id, user_id
                                         ) top_bids
                                    WHERE user_id = ' . $user_id . ' GROUP BY user_id;'));

        if (empty($value)) {
            return 0;
        }
        return $value[0]->sum;

    }

    public static function heldBalanceOnAuction($user_id, $auction_id) {

        $value = DB::select(DB::raw('SELECT SUM(max)
                                    FROM (  SELECT BID.auction_id, BID.user_id, MAX(BID.amount)
                                            FROM BID
                                            INNER JOIN AUCTION ON BID.auction_id=AUCTION.id
                                            WHERE AUCTION.state = \'Running\' AND AUCTION.id = ' . $auction_id . '
                                            GROUP BY auction_id, user_id
                                         ) top_bids
                                    WHERE user_id = ' . $user_id . ' GROUP BY user_id;'));

        if (empty($value)) {
            return 0;
        }
        return $value[0]->sum;
    }

    public static function getBanStates() {
        $states = DB::select(DB::raw("SELECT unnest(enum_range(NULL::penalty_type))::text AS type;"));

        return $states;
    }
}
