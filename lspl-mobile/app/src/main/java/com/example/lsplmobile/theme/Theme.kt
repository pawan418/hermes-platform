package com.example.lsplmobile.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val DarkColorScheme = darkColorScheme(
    primary = NeonCyan,
    secondary = NeonIndigo,
    tertiary = NeonTeal,
    background = ObsidianBg,
    surface = CardDarkBg,
    onPrimary = ObsidianBg,
    onSecondary = ObsidianBg,
    onTertiary = ObsidianBg,
    onBackground = TextPrimary,
    onSurface = TextPrimary,
    surfaceVariant = SurfaceLight,
    onSurfaceVariant = TextSecondary,
    outline = CardBorder,
    error = GlowRed
)

private val LightColorScheme = lightColorScheme(
    primary = NeonIndigo,
    secondary = NeonCyan,
    tertiary = NeonTeal,
    background = Color(0xFFF8FAFC),
    surface = Color.White,
    onPrimary = Color.White,
    onSecondary = Color.White,
    onTertiary = Color.White,
    onBackground = Color(0xFF0F172A),
    onSurface = Color(0xFF0F172A),
    surfaceVariant = Color(0xFFE2E8F0),
    onSurfaceVariant = Color(0xFF475569),
    outline = Color(0xFFCBD5E1),
    error = Color(0xFFEF4444)
)

@Composable
fun LSPLMobileTheme(
    darkTheme: Boolean = true, // Force dark theme by default for premium tech styling
    dynamicColor: Boolean = false, // Disable dynamic colors to maintain custom brand identity
    content: @Composable () -> Unit
) {
    // We always use the custom DarkColorScheme for the cyber-obsidian look, unless darkTheme is explicitly false
    val colorScheme = if (darkTheme) DarkColorScheme else LightColorScheme

    MaterialTheme(
        colorScheme = colorScheme,
        typography = Typography,
        content = content
    )
}
