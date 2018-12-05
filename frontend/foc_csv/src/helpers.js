/*
  Validate required fields in app config
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

/*
  Read first string from blob
*/
class FirstLineReader {
  constructor () {
    this.events = {}

    // i'm using 4kb because i don't hugging know how
    // to correctly process line_by_line non ascii text
    // it's seems that slice breaks unicode symbols and
    // i don't know how to merge them back, so i get this: �
    // if you know how to fight this problem, please send PR to repo!
    this.chunkSize = 4096
    this.readPos = 0
    this.reader = new FileReader()
    this.lines = []
    this.chunk = ''
    this.file = null
    this.readedLines = 0

    this.reader.onload = () => {
      this.chunk += this.reader.result
      this.process()
    }
  }

  /*
    Remove non printable characters
  */
  _fixString (str) {
    return str.replace(/\uFFFD/g, '')
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
      // it's a hack to remove � symbols, please see chunkSize comment above
      // this can drop characters in UX, but i think it's more comfortable for user
      let line = this._fixString(lines.shift())
      this.readedLines++

      if (this.readedLines === this.skipLines) {
        this._emit('line', [line])
      }
      else {
        this.chunk = lines.join('\n')
        this.step()
      }
    }
    else {
      if (this.readPos < this.file.size) {
        this.step()
      }
      else {
        this._emit('error')
      }
    }
  }

  read (file, encoding, skipLines = 0) {
    this.file = file
    this.lines = []
    this.chunk = ''
    this.readPos = 0
    this.encoding = encoding || 'UTF8'
    this.skipLines = parseInt(skipLines)

    this.step()
  }

  step () {
    let blob = this.file.slice(
      this.readPos,
      this.readPos + this.chunkSize
    )

    this.readPos += this.chunkSize

    this.reader.readAsText(blob, this.encoding)
  }
}

/*
  Parse csv headers - stupid split function:)
*/
const parseCsvHeaders = (raw, delimiter = ';') => {
  let clean = raw.replace(/(\r\n|\n)/gm, '')
  clean = clean.split('"').join('')
  return clean.split(delimiter)
}

/*
  Validate profile
  for now - just check keyField exists
*/
const validateProfile = (profile) => {
  // validate key field
  return profile.keyField && profile.bindings[profile.keyField] != null
}

export {
  validateAppConfig,
  FirstLineReader,
  parseCsvHeaders,
  validateProfile
}
