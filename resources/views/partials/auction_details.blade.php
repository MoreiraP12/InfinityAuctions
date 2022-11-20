<div>
    <h4 class="name">{{$details->name}}</h4>
    <h5 class="desc">{{$details->description}}</h5>
    <p class="time-rem">TIME REMAINING</p>
    @if ($details->state == "Running")
        <h5 id="final-date">{{$details->end_date}}</h5>
    @else
        <h5 id="final-date">{{strtoupper($details->state)}}</h5>
    @endif
    <p class="max-bid">TOP BID</p>
    @include('partials.bid', ['bid' => $bids->max(), 'start_amount' => $details->base_price])
    <form>
        <input id="user_id" hidden value="{{Auth::id()}}">
        <input type="number" id="bid_amount" name="amount" placeholder="Bid Amount">
        <button id="make_bid">BID</button>
    </form>
    <section class="price-suggestions">
        @if ($bids->max())
            {{-- Price Suggest 1 --}}
            <form>
                <input id="user_id" hidden value="{{Auth::id()}}">
                <input type="number" hidden id="bid_amount" name="amount" placeholder="Bid Amount" value="{{number_format((float)$bids->max()->amount*1.10, 2, '.', '')}}">
                <button type="submit">{{number_format((float)$bids->max()->amount*1.10, 2, '.', '')}}€</button>
            </form>
            {{-- Price Suggest 2 --}}
            <form>
                <input id="user_id" hidden value="{{Auth::id()}}">
                <input type="number" hidden id="bid_amount" name="amount" placeholder="Bid Amount" value="{{number_format((float)$bids->max()->amount*1.25, 2, '.', '')}}">
                <button type="submit">{{number_format((float)$bids->max()->amount*1.25, 2, '.', '')}}€</button>
            </form>
            {{-- Price Suggest 3 --}}
            <form>
                <input id="user_id" hidden value="{{Auth::id()}}">
                <input type="number" hidden id="bid_amount" name="amount" placeholder="Bid Amount" value="{{number_format((float)$bids->max()->amount*1.50, 2, '.', '')}}">
                <button type="submit">{{number_format((float)$bids->max()->amount*1.50, 2, '.', '')}}€</button>
            </form>
        @else
            {{-- Price Suggest 1 --}}
            <form>
                <input id="user_id" hidden value="{{Auth::id()}}">
                <input type="number" hidden id="bid_amount" name="amount" placeholder="Bid Amount" value="{{number_format((float)$details->base_price, 2, '.', '')}}">
                <button type="submit">{{number_format((float)$details->base_price*1, 2, '.', '')}}€</button>
            </form>
            {{-- Price Suggest 2 --}}
            <form>
                <input id="user_id" hidden value="{{Auth::id()}}">
                <input type="number" hidden id="bid_amount" name="amount" placeholder="Bid Amount" value="{{number_format((float)$details->base_price*1.10, 2, '.', '')}}">
                <button type="submit">{{number_format((float)$details->base_price*1.10, 2, '.', '')}}€</button>
            </form>
            {{-- Price Suggest 3 --}}
            <form>
                <input id="user_id" hidden value="{{Auth::id()}}">
                <input type="number" hidden id="bid_amount" name="amount" placeholder="Bid Amount" value="{{number_format((float)$details->base_price*1.25, 2, '.', '')}}">
                <button type="submit">{{number_format((float)$details->base_price*1.25, 2, '.', '')}}€</button>
            </form>
        @endif
    </section>
    @if ($details->buy_now)
        <form class="buy-now">
            <input id="user_id" hidden value="{{Auth::id()}}">
            <button type="submit">Buy now for {{number_format((float)$details->buy_now, 2, '.', '')}}€</button>
        </form>
    @endif
</div>
