package com.example.lsplmobile.data

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import kotlinx.serialization.json.Json
import java.io.OutputStreamWriter
import java.net.HttpURLConnection
import java.net.URL

object ApiClient {
    // 10.0.2.2 is the special loopback address pointing to the host machine running the emulator
    var baseUrl = "http://10.0.2.2:8000/"

    private val json = Json {
        ignoreUnknownKeys = true
        coerceInputValues = true
    }

    suspend fun getApiData(): ApiResponse = withContext(Dispatchers.IO) {
        val urlStr = "${baseUrl.trimEnd('/')}/api.php?action=get_data"
        val url = URL(urlStr)
        val conn = url.openConnection() as HttpURLConnection
        conn.requestMethod = "GET"
        conn.connectTimeout = 8000
        conn.readTimeout = 8000
        
        val code = conn.responseCode
        if (code in 200..299) {
            val responseText = conn.inputStream.bufferedReader().use { it.readText() }
            json.decodeFromString<ApiResponse>(responseText)
        } else {
            throw Exception("HTTP Error code: $code")
        }
    }

    suspend fun submitLead(
        portal: String,
        name: String,
        email: String,
        phone: String,
        service: String,
        duration: String,
        budget: String,
        message: String,
        leadType: String
    ): Boolean = withContext(Dispatchers.IO) {
        val urlStr = "${baseUrl.trimEnd('/')}/api.php?action=submit_lead"
        val url = URL(urlStr)
        val conn = url.openConnection() as HttpURLConnection
        conn.requestMethod = "POST"
        conn.setRequestProperty("Content-Type", "application/json")
        conn.doOutput = true
        conn.connectTimeout = 8000
        conn.readTimeout = 8000

        val payload = """
            {
                "portal": "$portal",
                "name": "${name.replace("\"", "\\\"")}",
                "email": "${email.replace("\"", "\\\"")}",
                "phone": "${phone.replace("\"", "\\\"")}",
                "service_selected": "${service.replace("\"", "\\\"")}",
                "duration_selected": "${duration.replace("\"", "\\\"")}",
                "budget": "${budget.replace("\"", "\\\"")}",
                "message": "${message.replace("\"", "\\\"")}",
                "type": "${leadType.replace("\"", "\\\"")}"
            }
        """.trimIndent()

        OutputStreamWriter(conn.outputStream).use { writer ->
            writer.write(payload)
            writer.flush()
        }

        val code = conn.responseCode
        code in 200..299
    }

    suspend fun detectCountry(): String? = withContext(Dispatchers.IO) {
        try {
            val url = URL("https://ipapi.co/json/")
            val conn = url.openConnection() as HttpURLConnection
            conn.requestMethod = "GET"
            conn.connectTimeout = 3000
            conn.readTimeout = 3000
            if (conn.responseCode in 200..299) {
                val responseText = conn.inputStream.bufferedReader().use { it.readText() }
                val match = "\"country_code\"\\s*:\\s*\"([^\"]+)\"".toRegex().find(responseText)
                match?.groupValues?.get(1)?.uppercase()
            } else {
                null
            }
        } catch (e: Exception) {
            null
        }
    }
}
