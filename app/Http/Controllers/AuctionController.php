<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\Image;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuctionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    public function handleImages($records){
        $res = array();
        foreach ($records as $record){
            if (isset($res[$record->id])){
                $res[$record->id] += $record->path;
            } else{
                $res[$record->id] = $record->path;
            }
        }
        return $res;
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Auction $auction
     * @return \Illuminate\Http\Response
     */
    public function show($auction_id)
    {
        $details = Auction::find($auction_id);
        $owner = $details->owner()->first();
        $name = $owner->name;
        $auctions = $owner->ownedAuctions()->where('auction.id', '<>', $auction_id)->get();
        $bids = $details->bids()->orderBy('amount')->get();
        $mostActive = (new Auction())->mostActive();
        $images = $details->images()->get('path');

        return view('pages.auction', compact('auction_id', 'details', 'bids', 'name', 'auctions', 'mostActive', 'images'));
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Auction $auction
     * @return \Illuminate\Http\Response
     */
    public function showSellForm()
    {
        return view('pages.sell');
    }

    public function sell(Request $request)
    {
        $auction = new Auction();

         try{
             $this->authorize('create', $auction);
             $postData = $request->only('images');
             $file = $postData['images'];

             $fileArray = array('image' => $file);

             $rules = array(
                 'image' => 'mimes:jpeg,jpg,png,gif|required|max:10000' // max 10000kb
             );

             $validator = Validator::make($fileArray, $rules);

             $validated = $request->validate([
                 'title' => 'required|min:1|max:255',
                 'desc' => 'required|min:1|max:255',
                 'baseprice' => 'required|numeric|gt:0',
                 'startdate' => 'required|date|after:now',
                 'enddate' => 'required|date|after:startdate',
                 'buynow' => 'nullable|numeric|gt:baseprice',
             ]);

             $auction->name = $validated['title'];
             $auction->description = $validated['desc'];
             $auction->base_price = $validated['baseprice'];
             $auction->start_date = $validated['startdate'];
             $auction->end_date = $validated['enddate'];
             $auction->buy_now = $validated['buynow'];
             $auction->state = "To be started";
             $auction->auction_owner_id = Auth::user()->id;

             $id = DB::table('auction')->max('id');
             $auction->id = $id+1;

             $auction->save();

             $imageController = new ImageController();
             foreach($request->file('images') as $key => $image)
             {
                 $imageController->store($image, 'AuctionImages/', $auction->id);
             }

             return redirect('auctions/' . $auction->id);
         } catch (AuthorizationException $exception){
             return redirect('sell')->withErrors("You don't have permissions to create an auction!");
         }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Auction $auction
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit($id)
    {
        $auction = Auction::find($id);

        return view('pages.sell')
            ->with('title', $auction->name)
            ->with('desc', $auction->description)
            ->with('baseprice', $auction->base_price)
            ->with('startdate', $auction->start_date)
            ->with('enddate', $auction->end_date)
            ->with('buynow', $auction->buy_now)
            ->with('auction_id', $auction->id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Auction $auction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        try{
            $auction = Auction::find($id);
            // $this->authorize('update', $auction);
            $validated = $request->validate([
                'title' => 'required|min:1|max:255',
                'desc' => 'required|min:1|max:255',
                'baseprice' => 'required|numeric|gt:0',
                'startdate' => 'required|date|after:now',
                'enddate' => 'required|date|after:startdate',
                'buynow' => 'nullable|numeric|gt:baseprice',
            ]);

            $auction->name = $validated['title'];
            $auction->description = $validated['desc'];
            $auction->base_price = $validated['baseprice'];
            $auction->start_date = $validated['startdate'];
            $auction->end_date = $validated['enddate'];
            $auction->buy_now = $validated['buynow'];

            $auction->save();
        } catch (AuthorizationException $exception){
            return redirect()->back(status: 403)->withErrors("You don't have permissions to edit this auction!");
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Auction $auction
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        $auction = Auction::find($id);
        try{
            $this->authorize('delete', $auction);

            $auction->state = 'Cancelled';

            $auction->save();
            return redirect('/');
        } catch(AuthorizationException $exception){
            return redirect('auctions/'.$id)->withErrors("You don't have permissions to cancel this auction! ");
        }
    }

    public function selectedAuctions()
    {
        if (Auth::user()===NULL)
            return NULL;
        return Auth::user()->followingAuctions()->get();
    }

    public function getAllBids($auction_id){
        return (new Auction())->getAllbids($auction_id);
    }
}
