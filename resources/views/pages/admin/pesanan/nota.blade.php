<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Nota {{ $order->code }} — {{ config('app.name', 'Sini Laundry') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* Lebar kertas 80mm (struk thermal), rapi juga saat dicetak di A4. */
            .nota {
                width: 76mm;
                max-width: 76mm;
                margin: 0 auto;
                padding: 4mm 2mm;
                font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
                color: #0F172A;
            }

            .nota-mono {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            }

            @media print {
                @page {
                    size: 80mm auto;
                    margin: 0;
                }

                body {
                    background: #fff;
                }

                .no-print {
                    display: none !important;
                }

                .nota {
                    width: 80mm;
                    max-width: 80mm;
                    padding: 3mm 2mm;
                }
            }
        </style>
    </head>
    <body class="bg-surface">
        <div class="min-h-screen px-3 py-6">
            <div class="nota rounded-xl bg-white p-5 shadow-sm">
                {{-- Kepala nota --}}
                <div class="text-center">
                    <p class="text-base font-semibold">{{ config('laundry.business.name') }}</p>
                    <p class="mt-1 text-xs text-[#475569]">{{ config('laundry.business.address') }}</p>
                    <p class="text-xs text-[#475569]">Telp: {{ config('laundry.business.phone') }}</p>
                </div>

                <div class="my-3 border-t border-dashed border-[#CBD5E1]"></div>

                {{-- Info pesanan --}}
                <table class="w-full text-xs">
                    <tr>
                        <td class="py-0.5 text-[#475569]">No. Pesanan</td>
                        <td class="py-0.5 text-end font-semibold nota-mono">{{ $order->code }}</td>
                    </tr>
                    <tr>
                        <td class="py-0.5 text-[#475569]">Tanggal masuk</td>
                        <td class="py-0.5 text-end">{{ \App\Support\Money::datetime($order->created_at) }}</td>
                    </tr>
                    <tr>
                        <td class="py-0.5 text-[#475569]">Estimasi selesai</td>
                        <td class="py-0.5 text-end">
                            {{ $order->estimated_done_at ? \App\Support\Money::datetime($order->estimated_done_at) : '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="py-0.5 text-[#475569]">Pelanggan</td>
                        <td class="py-0.5 text-end">{{ $order->customer?->name }}</td>
                    </tr>
                    <tr>
                        <td class="py-0.5 text-[#475569]">No. HP</td>
                        <td class="py-0.5 text-end nota-mono">{{ $order->customer?->phone }}</td>
                    </tr>
                    <tr>
                        <td class="py-0.5 text-[#475569]">Sumber</td>
                        <td class="py-0.5 text-end">{{ $order->source?->label() }}</td>
                    </tr>
                    <tr>
                        <td class="py-0.5 text-[#475569]">Kasir</td>
                        <td class="py-0.5 text-end">{{ $order->creator?->name ?? 'Pesanan online' }}</td>
                    </tr>
                </table>

                <div class="my-3 border-t border-dashed border-[#CBD5E1]"></div>

                {{-- Item --}}
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-[#475569]">
                            <th class="pb-1 text-start font-medium">Layanan</th>
                            <th class="pb-1 text-end font-medium">Qty</th>
                            <th class="pb-1 text-end font-medium">Harga</th>
                            <th class="pb-1 text-end font-medium">Sub</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr wire:key="nota-item-{{ $item->id }}">
                                <td class="py-1 pe-1">{{ $item->service?->name }}</td>
                                <td class="py-1 text-end">
                                    @if ($item->service?->unit?->value === 'pcs')
                                        {{ (int) $item->quantity }}
                                    @else
                                        {{ rtrim(rtrim(number_format((float) $item->quantity, 2, ',', '.'), '0'), ',') }}
                                    @endif
                                </td>
                                <td class="py-1 text-end">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                <td class="py-1 text-end">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="my-3 border-t border-dashed border-[#CBD5E1]"></div>

                {{-- Total dan pembayaran --}}
                <table class="w-full text-xs">
                    <tr>
                        <td class="py-0.5 font-semibold">TOTAL</td>
                        <td class="py-0.5 text-end font-semibold">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                    </tr>
                    @foreach ($order->payments as $payment)
                        <tr wire:key="nota-payment-{{ $payment->id }}">
                            <td class="py-0.5 text-[#475569]">
                                Dibayar ({{ $payment->method?->label() }},
                                {{ \App\Support\Money::datetime($payment->paid_at) }})
                            </td>
                            <td class="py-0.5 text-end">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="py-0.5 text-[#475569]">Sisa tagihan</td>
                        <td class="py-0.5 text-end">Rp {{ number_format($order->remainingAmount(), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="py-0.5 text-[#475569]">Status bayar</td>
                        <td class="py-0.5 text-end font-medium">{{ $order->payment_status?->label() }}</td>
                    </tr>
                    <tr>
                        <td class="py-0.5 text-[#475569]">Status cucian</td>
                        <td class="py-0.5 text-end font-medium">{{ $order->status?->label() }}</td>
                    </tr>
                </table>

                <div class="my-3 border-t border-dashed border-[#CBD5E1]"></div>

                {{-- Pengganti QR (tanpa paket tambahan): tautan teks + kode besar --}}
                <div class="text-center">
                    <p class="text-[10px] text-[#475569]">Cek status cucian:</p>
                    <p class="nota-mono text-[11px] font-semibold break-all">
                        {{ url('/cek-status?kode='.$order->code) }}
                    </p>
                    <p class="nota-mono mt-2 text-lg font-bold tracking-widest">{{ $order->code }}</p>
                </div>

                <p class="mt-4 text-center text-[11px] text-[#475569]">
                    Terima kasih telah menggunakan {{ config('laundry.business.name') }}.<br>
                    Simpan nota ini sebagai bukti pengambilan cucian.
                </p>
            </div>

            {{-- Tombol aksi (disembunyikan saat cetak) --}}
            <div class="no-print mx-auto mt-5 flex max-w-sm justify-center gap-3">
                <button type="button" onclick="window.print()"
                    class="inline-flex h-11 items-center justify-center rounded-xl bg-[#1479D6] px-6 text-sm font-semibold text-white transition hover:bg-[#1260AD]">
                    Cetak
                </button>

                <a href="{{ route('admin.pesanan.show', $order) }}"
                    class="inline-flex h-11 items-center justify-center rounded-xl border border-[#1479D6] bg-white px-6 text-sm font-semibold text-[#1479D6] transition hover:bg-[#EFF8FF]">
                    Kembali
                </a>
            </div>
        </div>
    </body>
</html>
