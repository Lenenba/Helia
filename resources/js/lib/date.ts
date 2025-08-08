import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import localizedFormat from 'dayjs/plugin/localizedFormat'
import 'dayjs/locale/fr'

dayjs.extend(relativeTime)
dayjs.extend(localizedFormat)
dayjs.locale('fr')

export function fromNow(input?: string | Date | number) {
    return input ? dayjs(input).fromNow() : ''
}

export function formatDate(input?: string | Date | number, fmt = 'LLL') {
    return input ? dayjs(input).format(fmt) : ''
}

export default dayjs
