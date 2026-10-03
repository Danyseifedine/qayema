<?php

namespace App\Http\Requests;

use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use App\Services\Orders\OrderDetails;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PlaceOrderRequest extends FormRequest
{
    /** The public menu is open to guests; abuse is handled by the limiter. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The guest's menu language, set before anything is checked: the errors
     * under the cart's fields, the WhatsApp text and the line names all come
     * back in the language they were reading.
     */
    protected function prepareForValidation(): void
    {
        app()->setLocale($this->guestLocale());
    }

    /** The language the guest was reading, when it is one of this menu's. */
    public function guestLocale(): string
    {
        $restaurant = $this->restaurant();
        $asked = $this->input('locale');

        return is_string($asked) && in_array($asked, $restaurant->menuLanguages(), true)
            ? $asked
            : MenuLanguages::default($restaurant);
    }

    /**
     * Shape only. Which dishes are real, which belong to this restaurant and
     * what they cost is settled in OrderPlacer against the database; a price
     * is never accepted from the page.
     *
     * The guest's details are asked for only when the page was built for
     * ordering in the menu (`mode`). Whether that is still how this
     * restaurant takes orders is the controller's question.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.dish_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            // The guest's choices, as ids: one option per variant, any add-ons.
            'items.*.options' => ['nullable', 'array', 'max:'.config('menu.dish_options.variants')],
            'items.*.options.*' => ['integer', 'min:1'],
            'items.*.addons' => ['nullable', 'array', 'max:'.config('menu.dish_options.addons')],
            'items.*.addons.*' => ['integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
            // The language the guest was reading (guestLocale()).
            'locale' => ['nullable', 'string', 'size:2'],
            // Pages from before ordering in the menu existed send no mode.
            'mode' => ['nullable', Rule::enum(OrderChannel::class)],
        ];

        if (! $this->inMenu()) {
            return $rules;
        }

        return [
            ...$rules,
            'fulfilment' => ['required', Rule::in($this->restaurant()->orderTypes())],
            'name' => ['required', 'string', 'max:60'],
            'phone_country' => ['required', 'string', Rule::in(array_keys((array) config('countries')))],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+() .\-]+$/'],
            'address' => ['nullable', 'required_if:fulfilment,'.Fulfilment::Delivery->value, 'string', 'max:500'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            // The page's id for this order, or for this change to one.
            'client_token' => ['required', 'uuid'],
            // A change says which version of the order it was made from.
            'version' => [$this->isChange() ? 'required' : 'prohibited', 'integer', 'min:0'],
            // Hidden from people; a form-filling bot types something in.
            'website' => ['nullable', 'max:0'],
        ];
    }

    /**
     * The number has to make a real international one: 6 to 15 digits with
     * the guest's country code.
     *
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        if (! $this->inMenu()) {
            return [];
        }

        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['phone', 'phone_country'])) {
                return;
            }

            $national = preg_replace('/\D+/', '', (string) $this->input('phone')) ?? '';

            if (strlen($national) < 6 || $this->internationalPhone() === null) {
                $validator->errors()->add('phone', __('Check your phone number.'));
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => __('Your order is empty.'),
            'items.max' => __('That is too many different dishes for one order.'),
            'items.*.quantity.max' => __('99 of one dish is the most we can take.'),
            'fulfilment.required' => __('Choose delivery or pickup.'),
            'fulfilment.in' => __('Choose delivery or pickup.'),
            'name.required' => __('Add your name so the restaurant knows who to ask for.'),
            'name.max' => __('Keep your name under 60 characters.'),
            'phone.required' => __('Add your phone number so the restaurant can call you.'),
            'phone.regex' => __('Check your phone number.'),
            'phone.max' => __('Check your phone number.'),
            'phone_country.*' => __('Choose your country code.'),
            'address.required_if' => __('Add your address for the delivery.'),
            'address.max' => __('Keep the address under 500 characters.'),
            'note.max' => __('Keep the note under 500 characters.'),
            'latitude.*' => __('Your location did not come through. Try again, or just type your address.'),
            'longitude.*' => __('Your location did not come through. Try again, or just type your address.'),
            'client_token.*' => __('That did not send. Please try again.'),
            'version.*' => __('That did not send. Please try again.'),
            'website.max' => __('That did not send. Please try again.'),
        ];
    }

    /** The channel the guest's page was built for. */
    public function mode(): OrderChannel
    {
        return OrderChannel::tryFrom((string) $this->input('mode')) ?? OrderChannel::WhatsApp;
    }

    /** What the order carries besides its lines, from the validated input. */
    public function details(): OrderDetails
    {
        if (! $this->inMenu()) {
            return new OrderDetails(note: $this->validated('note'));
        }

        $located = $this->validated('latitude') !== null;

        return new OrderDetails(
            channel: OrderChannel::Menu,
            note: $this->validated('note'),
            fulfilment: Fulfilment::from($this->validated('fulfilment')),
            name: $this->validated('name'),
            phone: '+'.$this->internationalPhone(),
            address: $this->validated('address'),
            latitude: $located ? (string) $this->validated('latitude') : null,
            longitude: $located ? (string) $this->validated('longitude') : null,
            clientToken: $this->validated('client_token'),
        );
    }

    /** Which version of the order a change was made from (OrderPlacer::change()). */
    public function version(): int
    {
        return (int) $this->validated('version');
    }

    /** Sent to an order already placed (its tracking token in the address). */
    private function isChange(): bool
    {
        return $this->route('token') !== null;
    }

    private function inMenu(): bool
    {
        return $this->mode() === OrderChannel::Menu;
    }

    private function internationalPhone(): ?string
    {
        return PhoneNumber::international((string) $this->input('phone_country'), (string) $this->input('phone'));
    }

    private function restaurant(): Restaurant
    {
        return $this->route('restaurant');
    }
}
