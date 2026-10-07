<?php

namespace App\Http\Requests;

use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use App\Services\Orders\OrderDetails;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PlaceOrderRequest extends FormRequest
{
    private ?DiningTable $table = null;

    private bool $tableLookedUp = false;

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
            // The code of the table the guest scanned (DiningTable), if any.
            'table' => ['nullable', 'string', 'max:16'],
        ];

        if (! $this->inMenu()) {
            return $rules;
        }

        // At the table the restaurant can see who ordered, so a name and a
        // number are only asked for when the food leaves the room.
        $dineIn = $this->input('fulfilment') === Fulfilment::DineIn->value;
        $asked = $dineIn ? 'nullable' : 'required';

        return [
            ...$rules,
            'fulfilment' => ['required', Rule::in($this->restaurant()->menuFulfilments())],
            'name' => [$asked, 'string', 'max:60'],
            'phone_country' => [$dineIn ? 'required_with:phone' : 'required', 'nullable', 'string', Rule::in(array_keys((array) config('countries')))],
            'phone' => [$asked, 'string', 'max:30', 'regex:/^[0-9+() .\-]+$/'],
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
            $this->checkTable($validator);

            if ($validator->errors()->hasAny(['phone', 'phone_country']) || ! $this->filled('phone')) {
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
            'fulfilment.required' => __('Choose how you would like your order.'),
            'fulfilment.in' => __('Choose how you would like your order.'),
            'table.*' => __('Scan the code on your table again.'),
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

    /** An order to the table the guest scanned, which is always placed in the menu. */
    public function isDineIn(): bool
    {
        return $this->inMenu() && $this->input('fulfilment') === Fulfilment::DineIn->value;
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
            // On WhatsApp the table only labels the message.
            return new OrderDetails(note: $this->validated('note'), table: $this->table());
        }

        $located = $this->validated('latitude') !== null;
        $fulfilment = Fulfilment::from($this->validated('fulfilment'));
        $phone = $this->internationalPhone();

        return new OrderDetails(
            channel: OrderChannel::Menu,
            note: $this->validated('note'),
            fulfilment: $fulfilment,
            name: $this->validated('name'),
            phone: $phone === null ? null : '+'.$phone,
            address: $this->validated('address'),
            latitude: $located ? (string) $this->validated('latitude') : null,
            longitude: $located ? (string) $this->validated('longitude') : null,
            clientToken: $this->validated('client_token'),
            table: $fulfilment === Fulfilment::DineIn ? $this->table() : null,
        );
    }

    /** The table the guest scanned, when its code is one of this restaurant's. */
    public function table(): ?DiningTable
    {
        $code = $this->input('table');

        if (! is_string($code) || $code === '') {
            return null;
        }

        // Looked up once: the rules and the details both ask.
        if ($this->tableLookedUp === false) {
            $this->tableLookedUp = true;
            $this->table = $this->restaurant()->diningTables()->where('code', $code)->first();
        }

        return $this->table;
    }

    /**
     * Dine-in needs the table the food goes to: the one scanned, or for a
     * change, the one the order already has. A code from a card the owner
     * has since replaced is not a table.
     */
    private function checkTable(Validator $validator): void
    {
        if ($this->input('fulfilment') !== Fulfilment::DineIn->value || $validator->errors()->has('fulfilment')) {
            return;
        }

        if ($this->table() !== null || ($this->isChange() && $this->orderBeingChanged()?->table_name !== null)) {
            return;
        }

        $validator->errors()->add('table', $this->filled('table')
            ? __('This table\'s code has changed. Scan the code on your table again.')
            : __('Scan the code on your table to order to it.'));
    }

    private function orderBeingChanged(): ?Order
    {
        return $this->restaurant()->orders()->where('tracking_token', (string) $this->route('token'))->first();
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
