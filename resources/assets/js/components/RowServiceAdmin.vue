<template>
  <tbody @click="href('#!service/'+s.id)">
    <tr>
      <td >
        <a :href="'#!service/'+s.id">{{ s.label }}</a>
      </td>
      <td
        :class="statusClass"
        :rowspan="rowspan"
      >
        <span
          :data-toggle="statusTooltip ? 'tooltip' : null"
          :title="statusTooltip"
        >
          <span class="pre-wrap">{{ statusMessage }}</span> &nbsp;
          <i class="glyphicon glyphicon-info-sign" v-if="statusTooltip"></i>
        </span>
      </td>
      <td  class="text-muted" :title="s.updated_at">
        <div>{{ s.updated_at | date }}</div>
        <div>{{ s.updated_by }}</div>
      </td>
    </tr>
  </tbody>
</template>

<script>
import RowServiceMixin from '../mixins/RowService'

export default {
  mixins: [RowServiceMixin],
  computed: {
    activeUsers () {
      return this.s.activeUsers.map(u => u.email) || []
    },
    ghostUsers () {
      return this.s.ghostUsers.map(u => u.email) || []
    }
  }
}
</script>
