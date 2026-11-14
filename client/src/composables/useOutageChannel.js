import { onBeforeUnmount, onMounted } from 'vue'
import { getEcho } from '@/services/echo'

export function useOutageChannel(onReportChanged) {
  let channel = null

  onMounted(() => {
    channel = getEcho().channel('outages')
    channel.listen('.report.changed', onReportChanged)
  })

  onBeforeUnmount(() => {
    getEcho().leaveChannel('outages')
    channel = null
  })
}
