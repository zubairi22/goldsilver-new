<script setup lang="ts">
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale
} from 'chart.js'
import { Bar } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const props = defineProps<{
    labels: string[]
    data: number[]
    color?: string
    prefix?: string
    suffix?: string
}>()

const chartData = {
    labels: props.labels,
    datasets: [
        {
            label: '',
            backgroundColor: props.color ?? '#10b981',
            data: props.data,
        }
    ]
}

const chartOptions = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: {
            ticks: {
                callback: (value) => {
                    return `${props.prefix ?? ''}${Number(value).toLocaleString()}${props.suffix ?? ''}`
                }
            }
        }
    },
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: ctx =>
                    `${props.prefix ?? ''}${ctx.raw.toLocaleString()}${props.suffix ?? ''}`
            }
        }
    }
}
</script>

<template>
    <Bar :data="chartData" :options="chartOptions" />
</template>
