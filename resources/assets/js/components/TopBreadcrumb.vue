<template>
  <div class="container container-breadcrumb">
    <ol class="breadcrumb">
      <li v-if="route.page=='home'">
        {{ route.tab === 'admin' ? 'Administrators' : route.tab ? 'Gebruikers' : 'Diensten' }}
      </li>
      <li v-else>
        <a href="#!home" @click="route.tab='users'" v-if="route.page==='user'"> Gebruikers </a>
        <a href="#home" @click="route.tab=0" v-else> Diensten </a>
      </li>

      <li v-if="!route.tab&&route.service>-1" :class="{active:route.page=='service'}">
        <a href="#service">{{ srv.label }}</a>
      </li>
      <li v-if="!route.tab&&level>1&&route.channel>-1" :class="{active:route.page=='channel'}">
        <a href="#channel" @click.prevent="toChannel()">{{ $root.routeChannel.label }}</a>
      </li>
      <li v-if="!route.tab&&level>2&&route.version>-1" :class="{active:route.page=='version'}">
        <a href="#version" @click.prevent="toVersion()">{{ $root.routeVersion.label }}</a>
      </li>
      <li v-if="!route.tab&&level>3&&route.calendar>-1" :class="{active:route.page=='calendar'}">
        <a href="#calendar" @click.prevent="toCalendar()">{{ $root.routeCalendar.label }}</a>
      </li>

      <li v-if="route.page=='service'">
        {{ route.tab2 && isOwner ? 'Gebruikers' : 'Kanalen' }}
      </li>
      <li v-if="route.page=='user'">
        {{ usr.name }}
      </li>
    </ol>
  </div>
</template>

<script>
export default {
  computed: {
    usr () {
      return this.route.id &&
        this.$root.users.find(u => u.id == this.route.id) ||
        console.warn('user page without user') || {
          name: 'Fout',
          services: []
        }
    },
    srv () {
      return this.service || this.$root.services.find(s => s.id === this.route.service) || this.$root.services[0] || {}
    },
    channels () {
      return this.srv && this.srv.channels || []
    },
    level () {
      switch(this.route.page) {
        case 'service': return 1
        case 'channel': return 2
        case 'version': return 3
        case 'calendar': return 4
      }
      return 0
    }
  }
}
</script>