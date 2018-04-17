import axios from 'axios'

/*
  Валидация обязательных полей
*/
const validateAppConfig = (config) => {
  const requiredFields = ['token', 'baseRoute', 'baseUrl']

  const keys = Object.keys(config)
  const values = Object.values(config)

  if (requiredFields.every(key => keys.includes(key)) &&
      values.every(value => !!value)) {
    return true
  }

  return false
}

// читает первую строку из blob
class FirstLineReader {
  constructor () {
    this.events = {}
    this.chunkSize = 512
    this.readPos = 0
    this.reader = new FileReader()
    this.lines = []
    this.chunk = ''
    this.file = null

    this.reader.onload = () => {
      this.chunk += this.reader.result
      console.log(this.chunk)
      this.process()
    }
  }

  on (event, cb) {
    this.events[event] = cb
  }

  _emit (event, args) {
    if (typeof this.events[event] === 'function') {
      this.events[event].apply(this, args)
    }
  }

  process () {
    if (/\n/.test(this.chunk)) {
      let lines = this.chunk.split('\n')
      this._emit('line', [lines[0]])
    }
    else {
      if (this.readPos < this.file.fileLength) {
        this.step()
      }
      else {
        this._emit('error')
      }
    }
  }

  read (file) {
    this.file = file
    this.lines = []
    this.chunk = ''
    this.readPos = 0

    this.step()
  }

  step () {
    let blob = this.file.slice(
      this.readPos,
      this.readPos + this.chunkSize
    )
    this.readPos += this.chunkSize

    this.reader.readAsText(blob)
  }
}

const parseCsvHeaders = (raw, delimiter = ';') => {
  let clean = raw.replace(/(\r\n|\n)/gm, '')
  clean = clean.split('"').join('')
  return clean.split(delimiter)
}

const submitData = async function (url, { data, profile }) {
  let request = new FormData()
  request.append('csv-file', data.csvFileRef.files[0])

  if (data.imagesZipFileRef && data.imagesZipFileRef.files.length > 0) {
    request.append('images-zip', data.imagesZipFileRef.files[0])
  }

  request.append('profile-json', JSON.stringify(profile))

  return axios.post(url, request)
}

const validateProfile = (profile) => {
  // validate key field
  return profile.keyField && profile.bindings.findIndex(val => val === profile.keyField) !== -1
}

const capitalizeFirstLetter = (str) => {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const mapVuexModels = (models) => {
  return models.reduce(function (prev, key) {
    prev[key] = {
      get () {
        let val = null
        try {
          val = this.$store.getters[key]
        }
        catch (e) {
          console.error(`missing getter ${key}`)
        }
        return val
      },
      set (val) {
        this.$store.dispatch(`set${capitalizeFirstLetter(key)}`, val)
      }
    }

    return prev
  }, {})
}

export {
  mapVuexModels,
  validateAppConfig,
  FirstLineReader,
  parseCsvHeaders,
  submitData,
  validateProfile
}
