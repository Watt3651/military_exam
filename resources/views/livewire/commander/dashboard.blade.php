<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100 p-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Dashboard - ผู้บังคับบัญชา</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        มุมมอง Read-only สำหรับติดตามสถิติการสมัครสอบและแนวโน้มเทียบปีก่อน
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('commander.reports.index', ['format' => 'pdf', 'year' => $yearFilter]) }}"
                       class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 text-white text-sm font-semibold hover:bg-primary-700">
                        Export รายงาน PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Filters</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="yearFilter" class="block text-sm font-medium text-gray-700">ปีการสอบ</label>
                    <select id="yearFilter" wire:model.live="yearFilter"
                            class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="">ทั้งหมด</option>
                        @foreach ($this->years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="testLocationFilter" class="block text-sm font-medium text-gray-700">สถานที่สอบ</label>
                    <select id="testLocationFilter" wire:model.live="testLocationFilter"
                            class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="">ทั้งหมด</option>
                        @foreach ($this->testLocations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }} ({{ $location->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="branchFilter" class="block text-sm font-medium text-gray-700">เหล่า</label>
                    <select id="branchFilter" wire:model.live="branchFilter"
                            class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="">ทั้งหมด</option>
                        @foreach ($this->branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100 p-6">
                <p class="text-sm font-medium text-gray-500">จำนวนผู้สมัครทั้งหมด</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $this->summary['total'] }}</p>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100 p-6">
                <p class="text-sm font-medium text-gray-500">จำนวนที่ออกหมายเลขแล้ว</p>
                <p class="mt-2 text-3xl font-bold text-green-700">{{ $this->summary['confirmed'] }}</p>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100 p-6">
                <p class="text-sm font-medium text-gray-500">จำนวนที่รอออกหมายเลข</p>
                <p class="mt-2 text-3xl font-bold text-amber-600">{{ $this->summary['pending'] }}</p>
            </div>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                YoY Comparison ({{ $this->yoy['current_year'] }} เทียบกับ {{ $this->yoy['previous_year'] }})
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @php
                    $yoyItems = [
                        'ผู้สมัครทั้งหมด' => $this->yoy['total'],
                        'ออกหมายเลขแล้ว' => $this->yoy['confirmed'],
                        'รอออกหมายเลข' => $this->yoy['pending'],
                    ];
                @endphp

                @foreach ($yoyItems as $label => $item)
                    <div class="rounded-lg border border-gray-200 p-4">
                        <p class="text-sm text-gray-500">{{ $label }}</p>
                        <p class="mt-2 text-xl font-bold text-gray-900">{{ $item['current'] }}</p>
                        <p class="text-xs text-gray-500">ปีก่อน: {{ $item['previous'] }}</p>
                        <p class="mt-2 text-sm font-semibold {{ $item['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $item['change'] >= 0 ? '+' : '' }}{{ $item['change'] }}
                            ({{ $item['change'] >= 0 ? '+' : '' }}{{ $item['percent'] }}%)
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100 p-6"
                 x-data="barChartComponent(@js($this->locationChart))"
                 x-init="init()"
                 x-effect="update(@js($this->locationChart))">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">จำนวนแยกตามสถานที่สอบ (Bar Chart)</h3>
                <div x-ref="chart" class="min-h-[320px]"></div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100 p-6"
                 x-data="pieChartComponent(@js($this->branchChart))"
                 x-init="init()"
                 x-effect="update(@js($this->branchChart))">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">จำนวนแยกตามเหล่า (Pie Chart)</h3>
                <div x-ref="chart" class="min-h-[320px]"></div>
            </div>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100 p-6"
             x-data="donutChartComponent(@js($this->levelChart))"
             x-init="init()"
             x-effect="update(@js($this->levelChart))">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">จำนวนแยกตามระดับ (Donut Chart)</h3>
            <div x-ref="chart" class="min-h-[320px]"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        function barChartComponent(initialData) {
            return {
                chart: null,
                init() {
                    this.chart = new ApexCharts(this.$refs.chart, this.options(initialData));
                    this.chart.render();
                },
                update(newData) {
                    if (!this.chart) return;
                    this.chart.updateOptions({ xaxis: { categories: newData.categories ?? [] } });
                    this.chart.updateSeries([{ name: 'ผู้สมัคร', data: newData.series ?? [] }]);
                },
                options(data) {
                    return {
                        chart: { type: 'bar', height: 320, toolbar: { show: false } },
                        series: [{ name: 'ผู้สมัคร', data: data.series ?? [] }],
                        xaxis: { categories: data.categories ?? [] },
                        plotOptions: { bar: { borderRadius: 4, horizontal: false } },
                        dataLabels: { enabled: true },
                        colors: ['#2563eb'],
                        noData: { text: 'ไม่มีข้อมูล' },
                    };
                }
            };
        }

        function pieChartComponent(initialData) {
            return {
                chart: null,
                init() {
                    this.chart = new ApexCharts(this.$refs.chart, this.options(initialData));
                    this.chart.render();
                },
                update(newData) {
                    if (!this.chart) return;
                    this.chart.updateOptions({ labels: newData.labels ?? [] });
                    this.chart.updateSeries(newData.series ?? []);
                },
                options(data) {
                    return {
                        chart: { type: 'pie', height: 320, toolbar: { show: false } },
                        labels: data.labels ?? [],
                        series: data.series ?? [],
                        legend: { position: 'bottom' },
                        noData: { text: 'ไม่มีข้อมูล' },
                    };
                }
            };
        }

        function donutChartComponent(initialData) {
            return {
                chart: null,
                init() {
                    this.chart = new ApexCharts(this.$refs.chart, this.options(initialData));
                    this.chart.render();
                },
                update(newData) {
                    if (!this.chart) return;
                    this.chart.updateOptions({ labels: newData.labels ?? [] });
                    this.chart.updateSeries(newData.series ?? []);
                },
                options(data) {
                    return {
                        chart: { type: 'donut', height: 320, toolbar: { show: false } },
                        labels: data.labels ?? [],
                        series: data.series ?? [],
                        legend: { position: 'bottom' },
                        noData: { text: 'ไม่มีข้อมูล' },
                    };
                }
            };
        }
    </script>
</div>
