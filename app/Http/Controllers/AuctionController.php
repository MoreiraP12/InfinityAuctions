<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\Category;
use App\Models\Notification;
use App\Models\User;
use App\Models\Bid;
use DateTime;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Validation\ValidationException;

class AuctionController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param $auction_id
     * @return Application|Factory|View
     */
    public function show($auction_id)
    {
        $details = Auction::findOrFail($auction_id);
        $owner = $details->owner()->first();
        $name = $owner->name;
        $auctions = $owner->ownedAuctions()->where('auction.id', '<>', $auction_id)->get();
        $bids = $details->bids()->orderBy('amount', 'desc')->get();
        $mostActive = Auction::mostActive();
        $images = $details->images()->get('path');
        $ratingDetails = $owner->getRatingDetails();
        $superUserMode = Auth::check() && (Auth::user()->is_admin || $details->auction_owner_id === Auth::user()->id);
        $categories = Category::auction_categories($auction_id);
        if (Auth::check()) {
            $followingAuctions = Auth::user()->followingAuctions()->get();
            return view('pages.auction', compact('auction_id', 'details', 'bids', 'name', 'auctions', 'mostActive', 'images', 'ratingDetails', 'superUserMode', 'followingAuctions', 'categories'));
        } else
            return view('pages.auction', compact('auction_id', 'details', 'bids', 'name', 'auctions', 'mostActive', 'images', 'ratingDetails', 'superUserMode', 'categories'));
    }

    public function showAuctionCheckout($auction_id)
    {
        $auction = Auction::find($auction_id);
        $user = Auth::user();
        $exploded = explode(' ', $user->name);
        $firstName = $exploded[0];
        $lastName = end($exploded);
        $address = $user->address;
        return view('pages.checkout', compact('auction', 'firstName', 'lastName', 'address'));
    }

    public function showAuctionCheckoutSuccess($auction_id)
    {
        $auction = Auction::find($auction_id);
        $auction->checkout = True;
        $auction->save();
        $cellphone = $auction->owner()->value("cellphone");
        return view('pages.checkout_success', compact('auction', 'cellphone'));
    }

    /**
     * Display the specified resource.
     *
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function showSellForm()
    {
        $categories = Category::all();
        $this->authorize('create', new Auction());
        return view('pages.sell', compact('categories'));
    }

    public function sell(Request $request)
    {
        $auction = new Auction();

        try {
            $this->authorize('create', $auction);
            $validated = $request->validate([
                'title' => 'required|min:1|max:255|regex:/^[a-zA-Z\s0-9,;\'.:\/()-]*$/',
                'desc' => 'required|min:1|max:255|regex:/^[a-zA-Z\s0-9,;\'.:\/()-]*$/',
                'images' => 'required|array|min:3',
                'baseprice' => 'required|numeric|gt:0',
                'startdate' => 'required|date|after_or_equal:' . (new \DateTime('now'))->format('m/d/Y'),
                'enddate' => 'required|date|after:startdate',
                'buynow' => 'nullable|numeric|gt:baseprice',
                'categories' => 'required|min:1',
                'images.*' => 'image',
            ], ['buynow.gt' => 'The "buy now" value must be greater than the base price.',
                'title.regex' => 'Invalid characters detected.',
                'desc.regex' => 'Invalid characters detected.',
                'images.min' => 'You need to select at least 3 images for your auction.',
                'images.*' => 'Your file number :position needs to be an image.',
                'categories.required' => "You need to select at least one category for your auction."]);


            $auction->name = $validated['title'];
            $auction->description = $validated['desc'];
            $auction->base_price = $validated['baseprice'];
            $auction->start_date = $validated['startdate'];
            $auction->end_date = $validated['enddate'];
            $auction->buy_now = $validated['buynow'];
            if ($validated['startdate'] == (new \DateTime('now'))->format('Y-m-d'))
                $auction->state = "Running";
            else
                $auction->state = "To be started";
            $auction->auction_owner_id = Auth::id();

            $auction->save();

            // connect auction to its categories
            foreach (Category::all() as $key => $category) {
                $cat = str_replace(' ', '', $category->name);
                if (in_array($cat, $validated['categories'])) {
                    $auction->categories()->attach($key + 1);
                }
            }

            foreach ($request->file('images') as $key => $image) {
                ImageController::store($image, 'AuctionImages/', $auction->id);
            }

            return redirect('auctions/' . $auction->id);
        } catch (AuthorizationException $exception) {
            return redirect('sell')->withErrors("You don't have permissions to create an auction!");
        } catch (QueryException $sqlExcept) {
            return redirect()->back()->withErrors("Invalid database parameters!");
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function edit($id)
    {
        $auction = Auction::findOrFail($id);
        $this->authorize('update', $auction);
        return view('pages.sell')
            ->with('title', $auction->name)
            ->with('images', $auction->images()->get())
            ->with('desc', $auction->description)
            ->with('baseprice', $auction->base_price)
            ->with('startdate', $auction->start_date)
            ->with('enddate', $auction->end_date)
            ->with('buynow', $auction->buy_now)
            ->with('auction_id', $auction->id)
            ->with('categories', Category::all())
            ->with('categoriesChosen', $auction->categories()->get());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $auction = Auction::findOrFail($id);
            $this->authorize('update', $auction);
            $validated = $request->validate([
                'title' => 'required|min:1|max:255|regex:/^[a-zA-Z\s0-9,;\'.:\/()-]*$/',
                'desc' => 'required|min:1|max:255|regex:/^[a-zA-Z\s0-9,;\'.:\/()-]*$/',
                'baseprice' => 'required|numeric|gt:0',
                'startdate' => 'required|date|after_or_equal:' . (new \DateTime('now'))->format('m/d/Y'),
                'enddate' => 'required|date|after:startdate',
                'buynow' => 'nullable|numeric|gt:baseprice',
                'images.*' => 'image',
            ], ['buynow.gt' => 'The "buy now" value must be greater than the base price.',
                'title.regex' => 'Invalid characters detected.',
                'images.*' => 'Your file number :position needs to be an image.',
                'desc.regex' => 'Invalid characters detected.']);

            if ($validated['startdate'] != substr($auction->start_date, 0, 10) && $auction->state == 'Running') {
                throw ValidationException::withMessages(["message" => "You can't change the start date on a running auction"]);
            }

            $auction->name = $validated['title'];
            $auction->description = $validated['desc'];
            $auction->base_price = $validated['baseprice'];
            $auction->start_date = $validated['startdate'];
            $auction->end_date = $validated['enddate'];
            $auction->buy_now = $validated['buynow'];

            $auction->save();

            $ids = array();
            foreach (Category::all() as $key => $category) {
                $cat = str_replace(' ', '', $category->name);
                if ($request->has($cat)) {
                    $ids[] = $category->id;
                }
            }
            if (count($ids) > 0)
                $auction->categories()->sync($ids);

            if ($request->file('images') !== null) {
                foreach ($request->file('images') as $image) {
                    ImageController::store($image, 'AuctionImages/', $auction->id);
                }
            }

            return redirect('auctions/' . $auction->id);
        } catch (AuthorizationException) {
            return redirect()->back()->withErrors("You don't have permissions to edit this auction!");
        }
    }

    public static function addNotificationsAuction($auction_id, $type)
    {
        $auction = Auction::find($auction_id);
        if ($type === 'Auction Canceled')
            $biddingUsers = $auction->biddersAndFollowers()->get();
        else
            $biddingUsers = $auction->biddingUsers()->get();

        foreach ($biddingUsers as $biddingUser) {
            $notification = new Notification();
            if ($type == 'Auction Ended' && $auction->getWinnerID() == $biddingUser->id)
                $notification->type = 'Auction Won';
            else
                $notification->type = $type;
            $notification->user_id = $biddingUser->id;
            $notification->auction_id = $auction_id;
            $notification->save();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return Application|RedirectResponse|Redirector
     */
    public function cancel($id)
    {
        $auction = Auction::find($id);
        try {
            $this->authorize('delete', $auction);

            $auction->state = 'Cancelled';

            $auction->save();
            AuctionController::addNotificationOwner($auction->id, 'Auction Canceled');
            AuctionController::addNotificationsAuction($auction->id, 'Auction Canceled');

            return redirect('/');
        } catch (AuthorizationException) {
            return redirect('auctions/' . $id)->withErrors("You don't have permissions to cancel this auction! ");
        }
    }

    public static function addNotificationOwner($auction_id, $type)
    {
        $owner = Auction::find($auction_id)->owner()->get()[0];

        $notification = new Notification();
        $notification->type = $type;
        $notification->user_id = $owner->id;
        $notification->auction_id = $auction_id;
        $notification->save();
    }

    public function selectedAuctions()
    {
        if (Auth::user() === NULL)
            return NULL;
        return Auth::user()->followingAuctions()->get();
    }

    public function getAllBids($auction_id)
    {
        return (new Auction())->getAllbids($auction_id);
    }

    public function endAuction($auction_id)
    {
        $auction = Auction::find($auction_id);
        $auction->end_date = new DateTime('now');
        $auction->save();
    }

    public static function updateAuctionsState()
    {
        // gathers all auction that ended just now and sends the respective notifications.
        // gathers all auction that will end 1 hour from now and sends the respective notifications.
        $auctionsToEnd = Auction::toEndAuctions();
        foreach ($auctionsToEnd as $auction) {
            AuctionController::addNotificationsAuction($auction->id, 'Auction Ended');
            $all_bids = Bid::all_bids($auction->id);
            if (count($all_bids) > 0) {
                $max_bid = $all_bids[0];
                $amount = $max_bid->amount;
                $user_id = $max_bid->user_id;
                User::removeBalance($user_id, (float)$amount);
                User::addBalance($auction->auction_owner_id, $amount * 0.95);
                User::addBalance(1002, $amount * 0.05);
            }
            AuctionController::addNotificationOwner($auction->id, 'Auction Canceled');
        }

        $auctionsEnding = Auction::nearEndAuctions();
        foreach ($auctionsEnding as $auction) {
            AuctionController::addNotificationOwner($auction->id, 'Auction Ending');
            AuctionController::addNotificationsAuction($auction->id, 'Auction Ending');
        }
        Auction::updateStates();
    }
}
