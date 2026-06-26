package com.example.lsplmobile

import androidx.compose.runtime.Composable
import androidx.navigation3.runtime.entryProvider
import androidx.navigation3.runtime.rememberNavBackStack
import androidx.navigation3.ui.NavDisplay
import com.example.lsplmobile.ui.main.MainScreen
import com.example.lsplmobile.ui.webview.WebViewScreenContent

@Composable
fun MainNavigation() {
  val backStack = rememberNavBackStack(Main)

  NavDisplay(
    backStack = backStack,
    onBack = { backStack.removeLastOrNull() },
    entryProvider =
      entryProvider {
        entry<Main> {
          MainScreen(onItemClick = { navKey -> backStack.add(navKey) })
        }
        entry<WebViewScreen> { key ->
          WebViewScreenContent(
            url = key.url,
            title = key.title,
            onBack = { backStack.removeLastOrNull() }
          )
        }
      },
  )
}
