type MappableToken = { name: string; value: string | string[] }

function slugify(text: string): string {
	return text
		.toLowerCase()
		.trim()
		.replace(/\s+/g, '-')
		.replace(/[^\w-]+/g, '')
}

function tokensToTailwind<T extends MappableToken>(
	tokens: T[],
): Record<string, string> {
	const response: Record<string, string> = {}

	for (const token of tokens) {
		const key = slugify(token.name)
		if (key in response) {
			throw new Error(`Duplicate token slug detected: ${key}`)
		}
		if (Array.isArray(token.value)) {
			response[key] = token.value.join(', ')
		} else {
			response[key] = String(token.value)
		}
	}

	return response
}

export default tokensToTailwind
