<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BidController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $bid = new Bid();
        $this->authorize('create', $bid);
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'auction_id' => 'required|integer',
            'user_id' => 'required|integer'
        ]);

        $bid->amount = $validated['amount'];
        $bid->auction_id = $validated['auction_id'];
        $bid->user_id = $validated['user_id'];
        $id = DB::table('bid')->max('id');
        $bid->id = $id+1;
        $bid->save();
        $bid->name = DB::table('users')->find($bid->user_id)->name;
        $bid->date=DB::table('bid')->find($id+1)->date;

        return $bid;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Bid  $bid
     * @return \Illuminate\Http\Response
     */
    public function show(Bid $bid)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Bid  $bid
     * @return \Illuminate\Http\Response
     */
    public function edit(Bid $bid)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Bid  $bid
     * @return \Illuminate\Http\Response
     */
    public function destroy(Bid $bid)
    {
        //
    }
}
