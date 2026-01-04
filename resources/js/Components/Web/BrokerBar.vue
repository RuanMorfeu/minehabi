<template>
    <div class="flex min-h-screen w-full bg-zinc-900 text-gray-100">
        <div class="min-h-screen w-[260px] px-4 py-4 text-center">
            <div>Expiration</div>
            <div
                class="mt-3 rounded border border-zinc-500 bg-zinc-700 text-center text-sm"
            >
                <div class="py-2">30s</div>
                <div
                    class="grid grid-cols-2 items-center justify-between divide-x-2 divide-zinc-500 border-t border-zinc-500"
                >
                    <div
                        class="flex h-8 cursor-pointer items-center justify-center hover:!bg-zinc-950/50"
                    >
                        -
                    </div>
                    <div class="flex h-8 items-center justify-center">+</div>
                </div>
            </div>

            <div class="mt-4">Investiment</div>
            <div
                class="mt-3 rounded border border-zinc-500 bg-zinc-700 text-center text-sm"
            >
                <div class="py-2">$100</div>
                <div
                    class="grid grid-cols-2 items-center justify-between divide-x-2 divide-zinc-500 border-t border-zinc-500"
                >
                    <div
                        class="flex h-8 cursor-pointer items-center justify-center hover:!bg-zinc-950/50"
                    >
                        -
                    </div>
                    <div class="flex h-8 items-center justify-center">+</div>
                </div>
            </div>

            <div class="mt-4">Your payout</div>
            <div class="mb-3 text-lg font-bold text-blue-500">$ 189</div>
            <div
                class="flex h-16 items-center justify-center gap-2 rounded-md border-2 border-blue-500 bg-gradient-to-bl from-blue-700 via-blue-600 to-blue-900 shadow"
            >
                BUY
            </div>

            <div
                class="mt-3 flex h-16 items-center justify-center gap-2 rounded-md bg-red-500"
            >
                SELL
            </div>
        </div>
        <div ref="monitor" class="w-full min-h-screen bg-black">
            <div ref="chartDiv"></div>
        </div>
    </div>
</template>
<script setup>
import { candlestickWidth } from "@/Services/CandleSize.js";

console.log(candlestickWidth(100, 200));

/*
https://tradingview.github.io/lightweight-charts/docs
 */
let randomFactor = 25 + Math.random() * 25;
const samplePoint = (i) =>
    i *
        (0.5 +
            Math.sin(i / 1) * 0.2 +
            Math.sin(i / 2) * 0.4 +
            Math.sin(i / randomFactor) * 0.8 +
            Math.sin(i / 50) * 0.5) +
    200 +
    i * 2;

function generateData(
    numberOfCandles = 500,
    updatesPerCandle = 5,
    startAt = 100,
) {
    const createCandle = (val, time) => ({
        time,
        open: val,
        high: val,
        low: val,
        close: val,
    });

    const updateCandle = (candle, val) => ({
        time: candle.time,
        close: val,
        open: candle.open,
        low: Math.min(candle.low, val),
        high: Math.max(candle.high, val),
    });

    randomFactor = 25 + Math.random() * 25;
    const date = new Date(Date.UTC(2024, 3, 25, 12, 0, 0, 0));
    const numberOfPoints = numberOfCandles * updatesPerCandle;
    const initialData = [];
    const realtimeUpdates = [];
    let lastCandle;
    let previousValue = samplePoint(-1);
    for (let i = 0; i < numberOfPoints; ++i) {
        if (i % updatesPerCandle === 0) {
            date.setUTCDate(date.getUTCDate() + 1);
        }
        const time = date.getTime() / 1000;
        let value = samplePoint(i);
        const diff = (value - previousValue) * Math.random();
        value = previousValue + diff;
        previousValue = value;
        if (i % updatesPerCandle === 0) {
            const candle = createCandle(value, time);
            lastCandle = candle;
            if (i >= startAt) {
                realtimeUpdates.push(candle);
            }
        } else {
            const newCandle = updateCandle(lastCandle, value);
            lastCandle = newCandle;
            if (i >= startAt) {
                realtimeUpdates.push(newCandle);
            } else if ((i + 1) % updatesPerCandle === 0) {
                initialData.push(newCandle);
            }
        }
    }

    return {
        initialData,
        realtimeUpdates,
    };
}

import { createChart } from "lightweight-charts";
import { onMounted, ref } from "vue";

const chartDiv = ref(null);
const monitor = ref(null);

onMounted(() => {
    const chartOptions = ref({
        layout: {
            textColor: "white",
            background: {
                type: "solid",
                color: "#000000",
            },
        },
        grid: {
            vertLines: { color: "#444" },
            horzLines: { color: "#444" },
        },
        width: monitor.value.clientWidth,
        height: monitor.value.clientHeight,
        timeScale: {
            timeVisible: true,
        },
    });

    console.log(chartOptions);

    const chart = createChart(chartDiv.value, chartOptions.value);

    const series = chart.addCandlestickSeries({
        upColor: "#26a69a",
        downColor: "#ef5350",
        borderVisible: false,
        wickUpColor: "#26a69a",
        wickDownColor: "#ef5350",
    });

    const data = generateData(2500, 20, 1000);

    series.setData(data.initialData);
    chart.timeScale().fitContent();
    chart.timeScale().scrollToPosition(5);
    chart.timeScale().applyOptions({
        borderColor: "#ea2dbb",
    });

    // Setting the border color for the horizontal ax

    // simulate real-time data
    function* getNextRealtimeUpdate(realtimeData) {
        for (const dataPoint of realtimeData) {
            yield dataPoint;
        }
        return null;
    }

    const streamingDataProvider = getNextRealtimeUpdate(data.realtimeUpdates);

    const intervalID = setInterval(() => {
        const update = streamingDataProvider.next();
        if (update.done) {
            clearInterval(intervalID);
            return;
        }
        series.update(update.value);
    }, 100);

    const myPriceLine = {
        price: 1234,
        color: "#3179F5",
        lineWidth: 2,
        lineStyle: 2, // LineStyle.Dashed
        axisLabelVisible: true,
        title: "my label",
    };

    series.createPriceLine(myPriceLine);
});

window.addEventListener("resize", () => {
    chart.resize(monitor.value.clientWidth, monitor.value.clientHeight);
});
</script>
