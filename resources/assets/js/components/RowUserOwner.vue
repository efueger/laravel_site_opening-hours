<template>
  <tbody>
    <tr :class="{'warning':!u.verified}" @click="href('#!user/'+u.id)">
      <td>
        {{ u.name }}
      </td>
      <td>
        {{ u.email }}
      </td>
      <td @click.prevent.stop>
        <select @change="changeRole" v-model="u.role">
          <option value="Owner">Ja</option>
          <option value="Member">Nee</option>
        </select>
      </td>
      <td v-if="u.verified" class="text-success">&checkmark;</td>
      <td v-else class="text-warning">&cross;</td>
      <td class="td-btn text-right" @click.stop>
        <button @click="$parent.banUser(u)" class="btn btn-default btn-icon">
          <i class="glyphicon glyphicon-ban-circle"></i>
        </button>
      </td>
    </tr>
  </tbody>
</template>

<script>
import { Hub } from '../lib.js'

export default {
  props: ['u'],
  computed: {
    statusClass () {
      return !this.u || !this.u || this.u > 200 ? 'warning' : 'text-success'
    },
    statusMessage () {
      if (!this.u) {
        return 'Geen kanalen'
      }
      if (!this.u) {
        return 'Niet ingevoerd'
      }
      if (this.u > 200) {
        return 'Verouderd'
      }
      return '✓ In orde'
    }
  },
  methods: {
    changeRole () {
      Hub.$emit('createRole', this.u)
    },
    invite () {
      Hub.$emit('inviteUser', this.u)
    }
  }
}
</script>
