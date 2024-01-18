{{--<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">--}}
{{--    <div class="overflow-scroll">--}}
{{--        <pre>{{ json_encode($getState(), JSON_PRETTY_PRINT) }}</pre>--}}
{{--        --}}{{-- {{ json_encode($getState(), JSON_PRETTY_PRINT) }}--}}
{{--    </div>--}}
{{--</x-dynamic-component>--}}
<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div
            {{
                $attributes
                    ->merge($getExtraAttributes(), escape: false)
                    ->class(['fi-in-key-value w-full rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'])
            }}
    >
        <table
                class="w-full table-auto divide-y divide-gray-200 dark:divide-white/5"
        >
            <thead>
            <tr>
                <th
                        scope="col"
                        class="px-3 py-2 text-start text-sm font-medium text-gray-700 dark:text-gray-200"
                >
                    {{ $getKeyLabel() }}
                </th>

                <th
                        scope="col"
                        class="px-3 py-2 text-start text-sm font-medium text-gray-700 dark:text-gray-200"
                >
                    {{ $getValueLabel() }}
                </th>
            </tr>
            </thead>

            <tbody
                    class="divide-y divide-gray-200 font-mono text-base sm:text-sm sm:leading-6 dark:divide-white/5"
            >
            @foreach ($getState() as $key => $value)
                    <?php
                    $testData = [
                        'test' => 'test',
                        'test2' => [
                            'test2' => 'test2',
                            'test3' => 'test3',
                        ],
                    ];
                    dd($testData);
                    if (is_array($testData)) {
                        $value = json_encode($testData, JSON_PRETTY_PRINT);
                        dd($value);
                    }
                    // if (is_array($value)) {
                    //     $value = json_encode($value, JSON_PRETTY_PRINT);
                    // }
                    ?>
                <tr
                        class="divide-x divide-gray-200 rtl:divide-x-reverse dark:divide-white/5"
                >
                    <td class="w-1/2 px-3 py-1.5">
                        {{ $key }}jeffff
                    </td>

                    <td class="w-1/2 px-3 py-1.5">
                        <pre>{{ $value }}</pre>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-dynamic-component>
