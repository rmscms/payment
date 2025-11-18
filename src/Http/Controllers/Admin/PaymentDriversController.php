<?php

namespace RMS\Payment\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RMS\Core\Contracts\Actions\ChangeBoolField;
use RMS\Core\Contracts\Filter\ShouldFilter;
use RMS\Core\Contracts\Form\HasForm;
use RMS\Core\Contracts\List\HasList;
use RMS\Core\Data\Field;
use RMS\Payment\Models\PaymentDriver;

class PaymentDriversController extends AdminController implements HasList, HasForm, ShouldFilter, ChangeBoolField
{
    public function table(): string
    {
        return 'payment_drivers';
    }

    public function modelName(): string
    {
        return PaymentDriver::class;
    }

    public function baseRoute(): string
    {
        return 'payment.drivers';
    }

    public function routeParameter(): string
    {
        return 'driver';
    }

    public function getFieldsForm(): array
    {
        return [
            Field::string('driver', trans('payment::admin.drivers.fields.driver'))
                ->required()
                ->withHint(trans('payment::admin.drivers.hints.driver')),
            Field::string('title', trans('payment::admin.drivers.fields.title'))
                ->required(),
            Field::string('slug', trans('payment::admin.drivers.fields.slug'))
                ->optional()
                ->withHint(trans('payment::admin.drivers.hints.slug')),
            Field::textarea('description', trans('payment::admin.drivers.fields.description'))
                ->optional(),
            Field::string('logo', trans('payment::admin.drivers.fields.logo'))
                ->optional()
                ->withHint(trans('payment::admin.drivers.hints.logo')),
            Field::string('documentation_url', trans('payment::admin.drivers.fields.documentation_url'))
                ->optional(),
            Field::number('sort_order', trans('payment::admin.drivers.fields.sort_order'))
                ->withDefaultValue(0),
            Field::boolean('is_active', trans('payment::admin.drivers.fields.is_active'))
                ->withDefaultValue(true),
        ];
    }

    public function getListFields(): array
    {
        return [
            Field::make('id')
                ->withTitle(trans('payment::admin.common.id'))
                ->sortable()
                ->width('80px'),
            Field::make('driver')
                ->withTitle(trans('payment::admin.drivers.fields.driver'))
                ->searchable()
                ->sortable()
                ->width('160px'),
            Field::make('title')
                ->withTitle(trans('payment::admin.drivers.fields.title'))
                ->searchable()
                ->sortable(),
            Field::make('description')
                ->withTitle(trans('payment::admin.drivers.fields.description'))
                ->width('240px'),
            Field::boolean('is_active')
                ->withTitle(trans('payment::admin.drivers.fields.is_active'))
                ->width('100px'),
            Field::make('sort_order')
                ->withTitle(trans('payment::admin.drivers.fields.sort_order'))
                ->sortable()
                ->width('100px'),
            Field::date('updated_at')
                ->withTitle(trans('payment::admin.common.updated_at'))
                ->filterType(Field::DATE)
                ->width('140px'),
        ];
    }

    public function boolFields(): array
    {
        return ['is_active'];
    }

    public function rules(): array
    {
        $id = request()->route($this->routeParameter());

        return [
            'driver' => ['required', 'string', 'max:100', 'unique:payment_drivers,driver,' . $id],
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', 'unique:payment_drivers,slug,' . $id],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'string', 'max:255'],
            'documentation_url' => ['nullable', 'url'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function syncFromConfig(Request $request)
    {
        $gateways = config('payment.gateways', []);

        DB::transaction(function () use ($gateways) {
            foreach ($gateways as $key => $gateway) {
                PaymentDriver::updateOrCreate(
                    ['driver' => $key],
                    [
                        'title' => $gateway['title'] ?? ucfirst($key),
                        'slug' => $gateway['slug'] ?? $key,
                        'description' => $gateway['description'] ?? null,
                        'logo' => $gateway['logo'] ?? null,
                        'documentation_url' => $gateway['documentation_url'] ?? null,
                        'sort_order' => $gateway['sort_order'] ?? 0,
                        'is_active' => $gateway['active'] ?? true,
                        'settings' => $gateway['settings'] ?? null,
                    ]
                );
            }
        });

        return redirect()
            ->route($this->prefix_route . $this->baseRoute() . '.index')
            ->with('status', trans('payment::admin.drivers.messages.synced'));
    }
}

