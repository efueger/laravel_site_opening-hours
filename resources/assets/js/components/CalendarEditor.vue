<template>
  <form class="wrapper-height100" @submit.prevent>
    <div class="wrapper-above-save">
      <div class="cal-img top-right" :class="'layer-'+cal.layer"></div>

      <!-- First calendar is always weekly -->
      <div v-if="!cal.layer">
        <h3>Stel de openingsuren in voor {{ $root.routeService.label }}. Op welke dagen is deze dienst normaal open?</h3>
        <p class="text-muted">Uitzonderingen kan je later instellen.</p>

        <event-editor v-for="(e, i) in cal.events" :parent="cal.events" :prop="i" @add-event="addEvent(i, e)" @rm="rmEvent(i)"></event-editor>

        <p v-if="!cal.events.length">
          <button type="button" @click="pushFirstEvent" class="btn btn-link">+ Voeg weekschema toe</button>
        </p>
      </div>

      <!-- Exception calendars must be renamed -->
      <!-- Choose from presets -->
      <div v-else-if="cal.label == 'Uitzondering'">
        <h3>Stel de uitzondering in.</h3>
        <div class="form-group required">
          <label>Naam uitzondering</label>
          <input type="text" class="form-control" v-model="calLabel" placeholder="Brugdagen, collectieve sluitingsdagen, ..." autofocus>
          <div class="help-block">Kies een specifieke naam die deze uitzondering beschrijft.</div>
        </div>
        <br>
        <transition name="slideup">
          <div v-if="showPresets">
            <h3>Voeg voorgedefineerde momenten toe</h3>
            <p class="text-muted">
              Klik op
              <em>Bewaar</em>
              om ook andere momenten toe te voegen
            </p>

            <div class="form-group">
             <h4>Herhalende vakantiedagen</h4>
              <div class="checkbox checkbox--preset" v-for="(preset, index) in presets">
                <hr v-if="preset.group" />
                <p class="text-muted" v-if="preset.group">
                  Geldig voor jaar {{preset.group}}
                </p>
                <label>
                  <div class="text-muted pull-right">{{ preset | dayMonth }}</div>
                  <input type="checkbox" name="preset" :value="index" v-model="presetSelection"> {{ preset.label }}
                </label>
              </div>
            </div>
          </div>
        </transition>
      </div>

      <!-- Other calendars have more options -->
      <div v-else>
        <h3>{{ cal.label }}</h3>
        <label>
          <input type="checkbox" :checked="cal.closinghours" @change="toggleClosing"> Sluitingsuren
        </label>
        <br>

        <event-editor v-for="(e, i) in cal.events" :parent="cal.events" :prop="i" @add-event="addEvent(i, e)" @rm="rmEvent(i)"></event-editor>

        <p>
          <button type="button" @click="pushEvent" class="btn btn-link">+ Voeg nieuwe periode of dag toe</button>
        </p>
      </div>
    </div>

    <div class="wrapper-save-btn">
      <div class="col-xs-12 text-right">
        <button type="button" class="btn btn-default pull-left" @click="rmCalendar()">Verwijder</button>
        <button type="button" class="btn btn-default" @click="cancel">Annuleer</button>
        <button type="submit" class="btn btn-primary" @click.prevent="showPresets = true" v-if="cal.label == 'Uitzondering' && !showPresets">Volgende</button>
        <button type="button" class="btn btn-danger" v-else-if="disabled" disabled>Bewaar</button>
        <button type="submit" class="btn btn-primary" @click="saveLabel" v-else-if="cal.label == 'Uitzondering'">Bewaar</button>
        <button type="button" class="btn btn-primary" @click="save" v-else>Bewaar</button>
      </div>
    </div>

<!--
    <pre>{{ cal }}</pre>
    <pre class="cal-render" style="margin-top:10em">{{ events }}</pre> -->
  </form>
</template>

<script>
import EventEditor from '../components/EventEditor.vue'
import { createEvent, createFirstEvent, presets } from '../defaults.js'
import { cleanEmpty, Hub, toDatetime } from '../lib.js'
import { MONTHS } from '../mixins/filters.js'

const fullDays = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag']


// Returns 10 character ISO string if date is valid
// or empty string if not
function toDateString (date, otherwise) {
  return !date ? toDateString(otherwise, new Date()) : typeof date === 'string' ? date.slice(0, 10) :
    date.toJSON ? date.toJSON().slice(0, 10) : toDateString(otherwise, new Date())
}

presets.sort((a, b) => a.start_date > b.start_date ? 1 : -1)
for (var i = presets.length - 2; i >= 0; i--) {
  const year = (presets[i + 1].start_date || '').slice(0, 4)
  if (year !== (presets[i].start_date || '').slice(0, 4)) {
    presets[i + 1].group = year
  }
}

export default {
  name: 'calendar-editor',
  props: ['cal', 'layer'],
  data () {
    return {
      // options: {}
      calLabel: '',
      days: ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'],
      fullDays,
      presets,
      presetSelection: [],
      showPresets: false,
    }
  },
  computed: {
    events () {
      return this.cal.events
    },
    disabled () {
      // Start before end
      if (this.events.filter(e => e.start_date > e.end_date).length) {
        return true
      }

      // Start before until
      if (this.events.filter(e => e.start_date.slice(0, 10) > e.until.slice(0, 10)).length) {
        return true
      }

      // Start before versionStart or end before versionEnd
      if (this.events.filter(e => e.start_date.slice(0, 10) < this.versionStartDate || e.until.slice(0, 10) > this.versionEndDate).length) {
        return true
      }

      // Name cannot be 'Uitzondering'
      if (this.cal.label === 'Uitzondering' && (!this.calLabel || this.calLabel === 'Uitzondering')) {
        return true
      }
    },
    versionStartDate() {
      return toDateString(this.$parent.version.start_date)
    },
    versionEndDate() {
      return toDateString(this.$parent.version.end_date)
    }
  },
  methods: {
    toggleClosing () {
      this.$set(this.cal, 'closinghours', !this.cal.closinghours)
    },
    pushEvent () {
      const start_date = toDatetime(this.$parent.version.start_date)
      this.cal.events.push(createEvent({
        start_date,
        label: this.cal.events.length + 1
      }))
    },
    pushFirstEvent () {
      this.cal.events.push(createFirstEvent(this.$parent.version))
    },
    addEvent (index, event) {
      event = Object.assign({}, event, { id: null })
      this.cal.events.splice(index, 0, event)
    },
    rmEvent (index) {
      this.cal.events.splice(index, 1)
    },
    cancel () {
      if (this.cal.label === 'Uitzondering') {
        return this.rmCalendar()
      }
      this.toVersion()
      this.$root.fetchVersion(true)
    },
    save () {
      if (this.disabled) {
        return console.warn('Expected valid calendar')
      }
      Hub.$emit('createCalendar', this.cal, true)
    },
    saveLabel () {
      if (!this.calLabel || this.calLabel === 'Uitzondering') {
        return console.warn('Expected calendar name')
      }
      this.cal.label = this.calLabel
      Hub.$emit('createCalendar', this.cal)
    },
    rmCalendar () {
      Hub.$emit('deleteCalendar', this.cal)
    }
  },
  created () {
    this.RRule = RRule || {}
  },
  mounted () {
    this.$set(this.cal, 'closinghours', !!this.cal.closinghours)
    if (!this.cal.events) {
      this.$set(this.cal, 'events', [])
    }
  },
  watch: {
    presetSelection (selection) {
      this.cal.events = []

      selection
        .map(s => this.presets[s])
        .forEach(({ start_date, rrule, until, ended }) => {
          // Repeating events
          if (rrule) {
            const start = toDatetime(start_date)
            const versionStart = toDatetime(this.$parent.version.start_date)
            start.setFullYear(versionStart.getFullYear())
            if (start < versionStart) {
              start.setFullYear(start.getFullYear() + 1)
            }
            this.cal.events.push(createEvent({
              start_date: start,
              until: toDatetime(this.$parent.version.end_date),
              rrule: rrule + (rrule === 'FREQ=YEARLY' ? ';BYMONTH=' + (start.getMonth() + 1) + ';BYMONTHDAY=' + start.getDate() : '')
            }))
          }

          // Specific events
          if (ended) {
            this.cal.events.push(createEvent({
              start_date: toDatetime(start_date),
              until: new Date(toDatetime(ended).valueOf() - 36e5),
              rrule: 'FREQ=DAILY'
            }))
          }
        })
    }
  },
  filters: {
    dayMonth (d) {
      const start = toDatetime(d.start_date)
      const until = d.ended ? new Date(toDatetime(d.ended).valueOf() - 23 * 36e5) : start
      if (start.getMonth() === until.getMonth()) {
        if (start.getDate() === until.getDate()) {
          return start.getDate() + ' ' + MONTHS[start.getMonth()]
        }
        return start.getDate() + ' - ' + until.getDate() + ' ' + MONTHS[start.getMonth()]
      }
      return start.getDate() + ' ' + MONTHS[start.getMonth()] + ' - ' + until.getDate() + ' ' + MONTHS[until.getMonth()]
    }
  },
  components: {
    EventEditor
  }
  // watch: {
  //   cal (v) {
  //     console.log('load cal', v)
  //     var options = RRule.parseString(this.rr)
  //     options.dtstart = new Date(2000, 1, 1)
  //     var r = new RRule(options)
  //     this.options = r.options
  //   },
  //   options: {
  //     deep: true,
  //     handler (v, o) {
  //       if (!v || !v.dtstart) {
  //         return
  //       }
  //       var str = new RRule(v).toString()
  //       if (this.cal.events && this.rr != str) {
  //         console.log('saves change to cal', str)
  //         this.cal.events[0].rrule = new RRule(v).toString()
  //       }
  //     }
  //   }
  // }
}
</script>
