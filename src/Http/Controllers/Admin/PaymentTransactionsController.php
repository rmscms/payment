<?php

namespace RMS\Payment\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Database\Query\Builder;
use RMS\Core\Contracts\Export\ShouldExport;
use RMS\Core\Contracts\Filter\ShouldFilter;
use RMS\Core\Contracts\List\HasList;
use RMS\Core\Contracts\Stats\HasStats;
use RMS\Core\Data\Field;
use RMS\Core\Data\StatCard;
use RMS\Core\View\HelperList\Generator as ListGenerator;
use RMS\Payment\Models\PaymentTransaction;

class PaymentTransactionsController extends AdminController implements HasList, ShouldFilter, ShouldExport, HasStats
{
    public function table(): string
    {
        return 'payment_transactions';
    }

    public function modelName(): string
    {
        return PaymentTransaction::class;
    }

    public function baseRoute(): string
    {
        return 'payment.transactions';
    }

    public function routeParameter(): string
    {
        return 'transaction';
    }

    public function getListFields(): array
    {
        return [
            Field::make('id')
                ->withTitle(trans('payment::admin.common.id'))
                ->sortable()
                ->width('80px'),
            Field::make('driver')
                ->withTitle(trans('payment::admin.transactions.fields.driver'))
                ->searchable()
                ->sortable()
                ->width('140px'),
            Field::make('order_id')
                ->withTitle(trans('payment::admin.transactions.fields.order_id'))
                ->searchable()
                ->sortable(),
            Field::make('authority')
                ->withTitle(trans('payment::admin.transactions.fields.authority'))
                ->searchable()
                ->width('220px'),
            Field::price('amount', trans('payment::admin.transactions.fields.amount'))
                ->withDefaultValue(0)
                ->width('140px'),
            Field::make('currency')
                ->withTitle(trans('payment::admin.transactions.fields.currency'))
                ->width('100px'),
            Field::make('status')
                ->withTitle(trans('payment::admin.transactions.fields.status'))
                ->customMethod('renderStatusBadge')
                ->width('160px'),
            Field::make('status_detail')
                ->withTitle(trans('payment::admin.transactions.fields.status_detail'))
                ->customMethod('renderStatusDetail')
                ->width('260px'),
            Field::date('created_at')
                ->withTitle(trans('payment::admin.transactions.fields.created_at'))
                ->filterType(Field::DATE)
                ->width('140px'),
            Field::date('verified_at')
                ->withTitle(trans('payment::admin.transactions.fields.verified_at'))
                ->filterType(Field::DATE)
                ->width('140px'),
        ];
    }

    public function rules(): array
    {
        return [];
    }

    protected function beforeGenerateList(ListGenerator &$generator): void
    {
        parent::beforeGenerateList($generator);
        $generator->removeActions('create');
        $generator->removeActions('edit');
        $generator->removeActions('destroy');
        $generator->create = false;
    }

    public function getStats(?Builder $query = null): array
    {
        $baseQuery = $query ? clone $query : PaymentTransaction::query();

        $total = (clone $baseQuery)->count();
        $success = (clone $baseQuery)->where('status', PaymentTransaction::STATUS_SUCCESS)->count();
        $failed = (clone $baseQuery)->where('status', PaymentTransaction::STATUS_FAILED)->count();
        $today = (clone $baseQuery)->whereDate('created_at', today())->count();

        return [
            StatCard::make(trans('payment::admin.transactions.stats.total'), (string) $total)
                ->withColor('primary')
                ->withIcon('ph-chart-line-up'),
            StatCard::make(trans('payment::admin.transactions.stats.success'), (string) $success)
                ->withColor('success')
                ->withIcon('ph-check-square'),
            StatCard::make(trans('payment::admin.transactions.stats.failed'), (string) $failed)
                ->withColor('danger')
                ->withIcon('ph-x-square'),
            StatCard::make(trans('payment::admin.transactions.stats.today'), (string) $today)
                ->withColor('warning')
                ->withIcon('ph-clock'),
        ];
    }

    public function renderStatusBadge($row): string
    {
        $status = (string) $row->status;
        $statuses = [
            PaymentTransaction::STATUS_INITIALIZED => ['class' => 'bg-secondary', 'label' => trans('payment::admin.transactions.statuses.initialized')],
            PaymentTransaction::STATUS_SENT => ['class' => 'bg-primary', 'label' => trans('payment::admin.transactions.statuses.sent')],
            PaymentTransaction::STATUS_RETURNED => ['class' => 'bg-info text-dark', 'label' => trans('payment::admin.transactions.statuses.returned')],
            PaymentTransaction::STATUS_SUCCESS => ['class' => 'bg-success', 'label' => trans('payment::admin.transactions.statuses.success')],
            PaymentTransaction::STATUS_FAILED => ['class' => 'bg-danger', 'label' => trans('payment::admin.transactions.statuses.failed')],
        ];

        $config = $statuses[$status] ?? ['class' => 'bg-dark', 'label' => e($status)];

        $badge = sprintf('<span class="badge %s">%s</span>', $config['class'], e($config['label']));

        if ($row->verified_at) {
            $badge .= '<br><small class="text-muted">'.trans('payment::admin.transactions.fields.verified_at').': '.$row->verified_at.'</small>';
        }

        return $badge;
    }

    public function renderStatusDetail($row): string
    {
        $detail = trim((string) $row->status_detail);

        if ($detail === '') {
            return '<span class="text-muted">—</span>';
        }

        return '<span class="text-wrap">'.e($detail).'</span>';
    }
}

