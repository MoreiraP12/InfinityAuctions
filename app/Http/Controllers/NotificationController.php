<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class NotificationController extends Controller
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

    public function addWishListNotification($user_id, $auction_id)
    {
        $notification = new Notification();
        $notification->id = $notification->getNextId();
        $notification->type = 'Wishlist Targeted';
        $notification->user_id = $user_id;
        $notification->auction_id = $auction_id;
        $notification->save();
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

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Notification $notification
     * @return \Illuminate\Http\Response
     */
    public function show(Notification $notification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Notification $notification
     * @return \Illuminate\Http\Response
     */
    public function edit(Notification $notification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Notification $notification
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Notification $notification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Notification $notification
     * @return string
     */
    public function destroy($id)
    {
        $notification = Notification::find($id);
        try {
            $this->authorize('delete', $notification);
            $notification->delete();
        } catch (AuthorizationException $exception) {
            return response($exception->getMessage(), 500);
        }
        return $notification;
    }
}
