<h1>{{ __('Welcome to the shop') }}</h1>
<p>{{ __("It's free delivery over :amount", ['amount' => '$50']) }}</p>
<p>@lang('Add to basket')</p>
<p>{{ trans('messages.checkout.title') }}</p>
<p>{{ trans_choice('messages.items', 3) }}</p>
<p>{{ __('Your order has shipped') }}</p>
