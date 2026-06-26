package com.example.lsplmobile

import androidx.navigation3.runtime.NavKey
import kotlinx.serialization.Serializable

@Serializable data object Main : NavKey

@Serializable data class WebViewScreen(val url: String, val title: String) : NavKey
