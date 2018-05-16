<template>
<div class="form-group">
  <label class="label label-default">
    <slot></slot>
  </label>

  <textarea ref="restore_profiles_data" class="form-control"></textarea>

  <button class="btn btn-danger" @click.prevent="restore"><i class="fa fa-floppy-o"></i> {{ $t('Restore') }}</button>
</div>
</template>

<script>
export default {
  props: {
    profiles: {
      type: Object,
      required: true
    }
  },
  methods: {
    restore () {
      try {
        let profiles = JSON.parse(this.$refs.restore_profiles_data.value)

        // keep default profile if user removed it
        if (!profiles['default']) {
          profiles['default'] = this.profiles.default
        }

        this.$emit('restore', profiles)
      }
      catch (e) {
        alert('Restore error!')
        console.error(e)
      }
    }
  }
}
</script>
