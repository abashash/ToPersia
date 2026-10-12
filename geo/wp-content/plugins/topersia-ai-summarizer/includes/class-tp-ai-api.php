<?php
/**
 * Low-level LLM client for the on-page panel.
 *
 * Dispatches to the right wire protocol per provider: OpenAI-compatible
 * /chat/completions (OpenAI, DeepSeek, Grok, Perplexity, Azure OpenAI, and
 * any other "custom" OpenAI-compatible gateway) via chat_openai(),
 * Anthropic's native /v1/messages via chat_anthropic(), and Google's
 * Gemini generateContent API via chat_gemini() - see TP_AI_Options::providers()
 * for which provider uses which protocol.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * HTTP client for the configured internal engine.
 */
class TP_AI_API {

	/**
	 * Whether an internal engine is fully configured and usable.
	 *
	 * @param string|null $engine_id Optional. Check one specific engine row
	 *                               instead of the default one.
	 * @return bool
	 */
	public static function is_configured( $engine_id = null ) {
		if ( empty( TP_AI_Options::get_field( 'internal_enabled' ) ) ) {
			return false;
		}

		$engine   = $engine_id ? TP_AI_Options::engine_by_id( $engine_id ) : TP_AI_Options::default_engine();
		$endpoint = TP_AI_Options::internal_endpoint( $engine );

		return '' !== $endpoint['base_url']
			&& '' !== $endpoint['model']
			&& '' !== $endpoint['api_key'];
	}

	/**
	 * Endpoint without its secret, for public filters (1.2.3 · P5-D355).
	 *
	 * @param array $endpoint Resolved endpoint.
	 * @return array
	 */
	private static function public_endpoint( array $endpoint ) {
		unset( $endpoint['api_key'] );

		return $endpoint;
	}

	/**
	 * Resolved endpoint for a given engine (or the default one).
	 *
	 * @param string|array|null $engine Engine row id, ad-hoc row, or null for the default.
	 * @return array
	 */
	public static function endpoint( $engine = null ) {
		if ( is_string( $engine ) && '' !== $engine ) {
			$engine = TP_AI_Options::engine_by_id( $engine );
		} elseif ( empty( $engine ) ) {
			$engine = TP_AI_Options::default_engine();
		}

		return TP_AI_Options::internal_endpoint( $engine );
	}

	/**
	 * Send a chat completion request.
	 *
	 * @param string            $system System message.
	 * @param string            $user   User message.
	 * @param array             $params Optional overrides (max_tokens, temperature).
	 * @param string|array|null $engine Which configured engine to use:
	 *                                  null (default) uses TP_AI_Options::default_engine();
	 *                                  a string is looked up as an engine row id;
	 *                                  an array is used as-is (an ad-hoc, not-yet-saved
	 *                                  row - e.g. the settings screen's "Test" button,
	 *                                  which must work before the row is saved).
	 * @return array { 'ok'=>bool, 'data'=>string, 'error'=>string, 'message'=>string }
	 */
	public static function chat( $system, $user, $params = array(), $engine = null ) {
		if ( is_string( $engine ) && '' !== $engine ) {
			$engine = TP_AI_Options::engine_by_id( $engine );
		} elseif ( empty( $engine ) ) {
			$engine = TP_AI_Options::default_engine();
		}

		$endpoint = TP_AI_Options::internal_endpoint( $engine );
		$key      = trim( (string) $endpoint['api_key'] );

		if ( '' === $endpoint['base_url'] || '' === $endpoint['model'] || '' === $key ) {
			return array(
				'ok'      => false,
				'error'   => 'not_configured',
				'message' => 'No internal engine configured.',
			);
		}

		/* دما از لایۀ سرویس‌ها اگر هست، وگرنه تنظیم خلاصه‌ساز — عدد ۰ تا ۲، بی تقسیم.  ۲٫۷ */
		$temperature = isset( $params['temperature'] ) ? (float) $params['temperature'] : ( class_exists( 'TP_AI_Core_Bridge' ) ? TP_AI_Core_Bridge::temperature() : TP_AI_Options::sanitize_temperature( TP_AI_Options::get_field( 'internal_temperature', 0.3 ) ) );
		$max_tokens  = isset( $params['max_tokens'] ) ? absint( $params['max_tokens'] ) : absint( TP_AI_Options::get_field( 'internal_max_tokens', 1200 ) );

		switch ( $endpoint['protocol'] ) {
			case 'anthropic':
				return self::chat_anthropic( $system, $user, $endpoint, $key, $temperature, $max_tokens );
			case 'gemini':
				return self::chat_gemini( $system, $user, $endpoint, $key, $temperature, $max_tokens );
			case 'azure':
				return self::chat_openai( $system, $user, $endpoint, $key, $temperature, $max_tokens, true );
			case 'openai':
			default:
				return self::chat_openai( $system, $user, $endpoint, $key, $temperature, $max_tokens, false );
		}
	}

	/**
	 * OpenAI-compatible /chat/completions call. Used for OpenAI, DeepSeek,
	 * Grok (xAI), Perplexity, "custom", and Azure OpenAI (Azure reuses the
	 * exact same request/response JSON shape - it only differs in how the
	 * URL is built and which header carries the key).
	 *
	 * @param string $system      System message.
	 * @param string $user        User message.
	 * @param array  $endpoint    Resolved endpoint (see TP_AI_Options::internal_endpoint()).
	 * @param string $key         API key.
	 * @param float  $temperature Sampling temperature.
	 * @param int    $max_tokens  Max output tokens.
	 * @param bool   $is_azure    Whether to use Azure's "api-key" header and
	 *                            skip appending "/chat/completions" (the
	 *                            admin-supplied Azure URL already points at
	 *                            the full deployment endpoint).
	 * @return array
	 */
	private static function chat_openai( $system, $user, $endpoint, $key, $temperature, $max_tokens, $is_azure ) {
		$body = array(
			'model'       => $endpoint['model'],
			'messages'    => array(
				array(
					'role'    => 'system',
					'content' => $system,
				),
				array(
					'role'    => 'user',
					'content' => $user,
				),
			),
			'temperature' => $temperature,
			'max_tokens'  => $max_tokens,
			'stream'      => false,
		);

		/** This filter is documented above in the original chat() body. */
		$body = apply_filters( 'tpas_api_payload', $body, self::public_endpoint( $endpoint ) );

		$url = $is_azure
			? $endpoint['base_url'] // Azure's admin-supplied URL is already the full deployment endpoint + api-version.
			: untrailingslashit( $endpoint['base_url'] ) . '/chat/completions';

		$headers = $is_azure
			? array(
				'api-key'      => $key,
				'Content-Type' => 'application/json',
			)
			: array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			);

		$args = apply_filters(
			'tpas_api_request_args',
			array(
				'timeout'     => 60,
				'redirection' => 5,
				'httpversion' => '1.1',
				'user-agent'  => 'ToPersia-AI-Summarizer/' . TP_AI_VERSION,
				'headers'     => $headers,
				'body'        => wp_json_encode( $body ),
			),
			$url
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'ok'      => false,
				'error'   => 'network',
				'message' => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$message = ( is_array( $data ) && isset( $data['error']['message'] ) )
				? (string) $data['error']['message']
				: 'HTTP ' . $code;

			return array(
				'ok'      => false,
				'error'   => 'http_' . $code,
				'message' => $message,
			);
		}

		$text = isset( $data['choices'][0]['message']['content'] )
			? (string) $data['choices'][0]['message']['content']
			: '';

		if ( '' === trim( $text ) ) {
			return array(
				'ok'      => false,
				'error'   => 'empty',
				'message' => 'Empty response.',
			);
		}

		return array(
			'ok'       => true,
			'data'     => $text,
			'finish'   => isset( $data['choices'][0]['finish_reason'] ) ? (string) $data['choices'][0]['finish_reason'] : '',
			'model'    => $endpoint['model'],
			'provider' => $endpoint['label'],
		);
	}

	/**
	 * Anthropic's native Messages API (/v1/messages). Distinct from OpenAI's
	 * protocol: the system prompt is a top-level field (not a message with
	 * role "system"), authentication uses the "x-api-key" header plus a
	 * required "anthropic-version" header, and the reply text is nested
	 * under content[0].text rather than choices[0].message.content.
	 *
	 * @param string $system      System message.
	 * @param string $user        User message.
	 * @param array  $endpoint    Resolved endpoint.
	 * @param string $key         API key.
	 * @param float  $temperature Sampling temperature.
	 * @param int    $max_tokens  Max output tokens.
	 * @return array
	 */
	private static function chat_anthropic( $system, $user, $endpoint, $key, $temperature, $max_tokens ) {
		$body = array(
			'model'       => $endpoint['model'],
			'system'      => $system,
			'messages'    => array(
				array(
					'role'    => 'user',
					'content' => $user,
				),
			),
			'temperature' => $temperature,
			'max_tokens'  => $max_tokens,
		);

		$body = apply_filters( 'tpas_api_payload', $body, self::public_endpoint( $endpoint ) );

		$url = untrailingslashit( $endpoint['base_url'] ) . '/messages';

		$args = apply_filters(
			'tpas_api_request_args',
			array(
				'timeout'     => 60,
				'redirection' => 5,
				'httpversion' => '1.1',
				'user-agent'  => 'ToPersia-AI-Summarizer/' . TP_AI_VERSION,
				'headers'     => array(
					'x-api-key'         => $key,
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				),
				'body'        => wp_json_encode( $body ),
			),
			$url
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'ok'      => false,
				'error'   => 'network',
				'message' => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$message = ( is_array( $data ) && isset( $data['error']['message'] ) )
				? (string) $data['error']['message']
				: 'HTTP ' . $code;

			return array(
				'ok'      => false,
				'error'   => 'http_' . $code,
				'message' => $message,
			);
		}

		$text = '';
		if ( isset( $data['content'] ) && is_array( $data['content'] ) ) {
			foreach ( $data['content'] as $block ) {
				if ( isset( $block['type'], $block['text'] ) && 'text' === $block['type'] ) {
					$text .= $block['text'];
				}
			}
		}

		if ( '' === trim( $text ) ) {
			return array(
				'ok'      => false,
				'error'   => 'empty',
				'message' => 'Empty response.',
			);
		}

		return array(
			'ok'       => true,
			'data'     => $text,
			'finish'   => isset( $data['stop_reason'] ) ? (string) $data['stop_reason'] : '',
			'model'    => $endpoint['model'],
			'provider' => $endpoint['label'],
		);
	}

	/**
	 * Google's Generative Language API (Gemini). Distinct from OpenAI's
	 * protocol: the API key travels as a "?key=" query parameter (not a
	 * header), the model is baked into the URL path rather than the body,
	 * content uses a contents[]/parts[] structure with a separate
	 * top-level systemInstruction, and the reply text is nested under
	 * candidates[0].content.parts[0].text.
	 *
	 * @param string $system      System message.
	 * @param string $user        User message.
	 * @param array  $endpoint    Resolved endpoint.
	 * @param string $key         API key.
	 * @param float  $temperature Sampling temperature.
	 * @param int    $max_tokens  Max output tokens.
	 * @return array
	 */
	private static function chat_gemini( $system, $user, $endpoint, $key, $temperature, $max_tokens ) {
		$body = array(
			'systemInstruction' => array(
				'parts' => array( array( 'text' => $system ) ),
			),
			'contents'          => array(
				array(
					'role'  => 'user',
					'parts' => array( array( 'text' => $user ) ),
				),
			),
			'generationConfig'  => array(
				'temperature'     => $temperature,
				'maxOutputTokens' => $max_tokens,
			),
		);

		$body = apply_filters( 'tpas_api_payload', $body, self::public_endpoint( $endpoint ) );

		/*
		 * کلید در سرآیند x-goog-api-key (همان SDK رسمی googleapis/python-genai)، نه در
		 * نشانی: نشانی در لاگ‌ها ثبت می‌شود و کلید هرگز نباید در لاگ بیاید.  P5-D249
		 */
		$url = untrailingslashit( $endpoint['base_url'] ) . '/models/' . rawurlencode( $endpoint['model'] ) . ':generateContent';

		$args = apply_filters(
			'tpas_api_request_args',
			array(
				'timeout'     => 60,
				'redirection' => 5,
				'httpversion' => '1.1',
				'user-agent'  => 'ToPersia-AI-Summarizer/' . TP_AI_VERSION,
				'headers'     => array(
					'Content-Type'   => 'application/json',
					'x-goog-api-key' => $key,
				),
				'body'        => wp_json_encode( $body ),
			),
			$url
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'ok'      => false,
				'error'   => 'network',
				'message' => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$message = ( is_array( $data ) && isset( $data['error']['message'] ) )
				? (string) $data['error']['message']
				: 'HTTP ' . $code;

			return array(
				'ok'      => false,
				'error'   => 'http_' . $code,
				'message' => $message,
			);
		}

		$text = '';
		if ( isset( $data['candidates'][0]['content']['parts'] ) && is_array( $data['candidates'][0]['content']['parts'] ) ) {
			foreach ( $data['candidates'][0]['content']['parts'] as $part ) {
				if ( isset( $part['text'] ) ) {
					$text .= $part['text'];
				}
			}
		}

		if ( '' === trim( $text ) ) {
			return array(
				'ok'      => false,
				'error'   => 'empty',
				'message' => 'Empty response.',
			);
		}

		return array(
			'ok'       => true,
			'data'     => $text,
			'finish'   => isset( $data['candidates'][0]['finishReason'] ) ? (string) $data['candidates'][0]['finishReason'] : '',
			'model'    => $endpoint['model'],
			'provider' => $endpoint['label'],
		);
	}

	/**
	 * Tiny connectivity test used from the settings screen.
	 *
	 * @param string|array|null $engine Engine row id, ad-hoc row, or null for the default.
	 * @return array
	 */
	public static function test_key( $engine = null ) {
		return self::chat(
			'You are a connectivity check. Reply with the single word OK and nothing else.',
			'Connectivity check.',
			array(
				'temperature' => 0,
				'max_tokens'  => 8,
			),
			$engine
		);
	}
}