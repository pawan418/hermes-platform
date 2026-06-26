<?php

namespace App\Modules\ProposalGenerator\Services;

use App\Modules\ProposalGenerator\Models\Proposal;

class ProposalService
{
    /**
     * Compile a beautiful HTML page from the Proposal attributes.
     */
    public function compileHtml(Proposal $proposal): string
    {
        $tenant = $proposal->tenant;
        $branding = $tenant?->getSetting('branding', []);
        $primaryColor = $branding['colors']['primary'] ?? '#4f46e5';

        $pricing = $proposal->pricing_details ?? [];
        $items = $pricing['items'] ?? [];
        $milestones = $pricing['milestones'] ?? [];
        
        $subtotal = 0.00;
        foreach ($items as $item) {
            $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
        }

        $discount = $pricing['discount'] ?? 0.00;
        $taxRate = $pricing['tax_rate'] ?? 0.00;
        
        $discountAmount = $subtotal * ($discount / 100.00);
        $taxable = $subtotal - $discountAmount;
        $taxAmount = $taxable * ($taxRate / 100.00);
        $total = $taxable + $taxAmount;

        $logoHtml = $branding['logo'] 
            ? "<img src='{$branding['logo']}' class='h-12' />"
            : "<span class='text-2xl font-bold tracking-tight text-slate-900'>{$tenant->name}</span>";

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemTotal = number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2);
            $itemPrice = number_format($item['price'] ?? 0, 2);
            $itemsHtml .= "
            <tr class='border-b border-slate-100'>
                <td class='py-4 text-sm font-medium text-slate-900'>{$item['name']}</td>
                <td class='py-4 text-sm text-slate-500 text-center'>{$item['quantity']}</td>
                <td class='py-4 text-sm text-slate-500 text-right'>\${$itemPrice}</td>
                <td class='py-4 text-sm font-semibold text-slate-900 text-right'>\${$itemTotal}</td>
            </tr>";
        }

        $milestonesHtml = '';
        foreach ($milestones as $index => $milestone) {
            $mNum = $index + 1;
            $milestonesHtml .= "
            <div class='relative pl-8 pb-6 border-l border-slate-200 last:pb-0 last:border-0'>
                <div class='absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-indigo-600 border-2 border-white'></div>
                <h4 class='text-sm font-semibold text-slate-900'>Milestone {$mNum}: {$milestone['title']}</h4>
                <p class='text-xs text-slate-500 mt-1'>{$milestone['description']}</p>
                <div class='text-xs font-medium text-slate-950 mt-1'>Estimated Delivery: {$milestone['delivery_days']} Days</div>
            </div>";
        }

        $signatureBlock = $proposal->signature_path 
            ? "<img src='" . asset('storage/' . $proposal->signature_path) . "' class='h-16 mx-auto' />"
            : "<div class='border-b border-dashed border-slate-300 h-16 w-48 mx-auto'></div>";

        $signedDate = $proposal->signed_at 
            ? $proposal->signed_at->format('M d, Y H:i') 
            : 'Awaiting signature';

        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>{$proposal->title}</title>
            <script src='https://cdn.tailwindcss.com'></script>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
                body { font-family: 'Outfit', sans-serif; }
            </style>
        </head>
        <body class='bg-slate-50 p-8 md:p-16'>
            <div class='max-w-4xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-100 p-8 md:p-12'>
                <!-- Header -->
                <div class='flex justify-between items-center border-b border-slate-100 pb-8'>
                    <div>{$logoHtml}</div>
                    <div class='text-right'>
                        <span class='inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 capitalize'>
                            {$proposal->status}
                        </span>
                        <p class='text-xs text-slate-400 mt-2'>Proposal ID: #PROP-{$proposal->id}</p>
                    </div>
                </div>

                <!-- Intro -->
                <div class='mt-8'>
                    <h1 class='text-3xl font-bold text-slate-900'>{$proposal->title}</h1>
                    <p class='text-sm text-slate-500 mt-4 leading-relaxed'>{$proposal->description}</p>
                </div>

                <!-- Scope / Terms -->
                <div class='mt-10'>
                    <h3 class='text-lg font-bold text-slate-900 border-b border-slate-100 pb-2'>Scope & Terms of Engagement</h3>
                    <div class='text-sm text-slate-600 mt-4 prose max-w-none leading-relaxed'>
                        {$proposal->content}
                    </div>
                </div>

                <!-- Milestones -->
                " . (!empty($milestones) ? "
                <div class='mt-10'>
                    <h3 class='text-lg font-bold text-slate-900 border-b border-slate-100 pb-2 mb-6'>Milestones & Deliverables</h3>
                    <div class='mt-4'>
                        {$milestonesHtml}
                    </div>
                </div>" : '') . "

                <!-- Pricing Table -->
                <div class='mt-10'>
                    <h3 class='text-lg font-bold text-slate-900 border-b border-slate-100 pb-2 mb-4'>Commercial Pricing</h3>
                    <table class='w-full'>
                        <thead>
                            <tr class='border-b border-slate-200 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider'>
                                <th class='pb-3'>Item Name</th>
                                <th class='pb-3 text-center'>Qty</th>
                                <th class='pb-3 text-right'>Unit Price</th>
                                <th class='pb-3 text-right'>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$itemsHtml}
                        </tbody>
                    </table>

                    <!-- Totals Grid -->
                    <div class='mt-6 flex justify-end'>
                        <div class='w-64 space-y-3 text-sm'>
                            <div class='flex justify-between text-slate-500'>
                                <span>Subtotal</span>
                                <span>\$" . number_format($subtotal, 2) . "</span>
                            </div>
                            " . ($discount > 0 ? "
                            <div class='flex justify-between text-emerald-600'>
                                <span>Discount ({$discount}%)</span>
                                <span>-\$" . number_format($discountAmount, 2) . "</span>
                            </div>" : '') . "
                            " . ($taxRate > 0 ? "
                            <div class='flex justify-between text-slate-500'>
                                <span>Tax ({$taxRate}%)</span>
                                <span>\$" . number_format($taxAmount, 2) . "</span>
                            </div>" : '') . "
                            <div class='flex justify-between text-base font-bold text-slate-900 pt-3 border-t border-slate-100'>
                                <span>Total Price</span>
                                <span>\$" . number_format($total, 2) . "</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Signatures -->
                <div class='mt-16 border-t border-slate-100 pt-10 grid grid-cols-2 gap-8 text-center'>
                    <div>
                        <p class='text-xs font-semibold text-slate-400 uppercase tracking-wider'>Prepared By</p>
                        <div class='h-16 flex items-center justify-center'>
                            <span class='text-sm font-medium text-slate-700'>{$tenant->name}</span>
                        </div>
                        <p class='text-xs text-slate-400 mt-2'>Provider Representative</p>
                    </div>
                    <div>
                        <p class='text-xs font-semibold text-slate-400 uppercase tracking-wider'>Client Signature Authorization</p>
                        <div class='h-16 flex items-center justify-center'>
                            {$signatureBlock}
                        </div>
                        <p class='text-xs text-slate-800 mt-2 font-medium'>{$signedDate}</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
